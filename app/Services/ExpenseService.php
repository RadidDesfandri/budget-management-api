<?php

namespace App\Services;

use App\Repositories\BudgetRepository;
use App\Repositories\ExpenseRepository;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        protected ExpenseRepository $expenseRepository,
        protected FileStorageService $fileStorageService,
        protected BudgetRepository $budgetRepository,
    ) {}

    public function getExpenses(
        array $filters,
        $organization_id,
        $user_id,
        $role,
    ) {
        return $this->expenseRepository->paginate(
            $organization_id,
            $filters,
            $user_id,
            $role,
        );
    }

    public function getExpense($expense_id, $organization_id)
    {
        $expense = $this->expenseRepository->findById(
            $expense_id,
            $organization_id,
        );

        if (!$expense) {
            throw new Exception("Expense not found", 404);
        }

        return $expense->load(
            "category:id,name,icon,icon_color,background_color",
            "user:id,name,avatar_url",
        );
    }

    public function createExpense(array $data, ?UploadedFile $receipt = null)
    {
        return DB::transaction(function () use ($data, $receipt) {
            $date = Carbon::parse($data["expense_date"]);
            $year = $date->year;
            $month = $date->month;

            $budget = $this->budgetRepository->getBudgetByRange(
                $data["organization_id"],
                $data["category_id"],
                $year,
                $month,
            );

            $data["status"] = "pending";
            $data["budget_id"] = $budget?->id;

            $expense = $this->expenseRepository->create($data);

            if ($receipt) {
                $receiptUrl = $this->fileStorageService->storeExpenseReceipt(
                    $receipt,
                    $expense->organization_id,
                    $expense->id,
                );
                $expense = $this->expenseRepository->update($expense, [
                    "receipt_url" => $receiptUrl,
                ]);
            }

            return $expense;
        });
    }

    public function updateExpense(
        $expense_id,
        $organization_id,
        array $data,
        ?UploadedFile $receipt = null,
        $user_id = null,
        $role = null,
    ) {
        return DB::transaction(function () use (
            $expense_id,
            $organization_id,
            $data,
            $receipt,
            $user_id,
            $role,
        ) {
            $expense = $this->expenseRepository->findById(
                $expense_id,
                $organization_id,
            );

            if (!$expense) {
                throw new Exception("Expense not found", 404);
            }

            if (
                in_array($role, ["member", "finance"]) &&
                $expense->user_id !== $user_id
            ) {
                throw new Exception(
                    "You can only update your own expenses.",
                    403,
                );
            }

            if ($expense->status !== "pending") {
                throw new Exception(
                    "Only pending expenses can be updated.",
                    403,
                );
            }

            if ($receipt) {
                if ($expense->receipt_url) {
                    $this->fileStorageService->deleteFile(
                        $expense->receipt_url,
                    );
                }

                $data[
                    "receipt_url"
                ] = $this->fileStorageService->storeExpenseReceipt(
                    $receipt,
                    $expense->organization_id,
                    $expense->id,
                );
            }

            return $this->expenseRepository->update($expense, $data);
        });
    }

    public function deleteExpense($expense_id, $organization_id)
    {
        return DB::transaction(function () use ($expense_id, $organization_id) {
            $expense = $this->expenseRepository->findById(
                $expense_id,
                $organization_id,
            );

            if (!$expense) {
                throw new Exception("Expense not found", 404);
            }

            if ($expense->status === "approved") {
                throw new Exception(
                    "Approved expenses cannot be deleted.",
                    403,
                );
            }

            if ($expense->receipt_url) {
                $this->fileStorageService->deleteFile($expense->receipt_url);
            }

            return $this->expenseRepository->delete($expense);
        });
    }

    public function approveExpense($expense_id, $organization_id)
    {
        return DB::transaction(function () use ($expense_id, $organization_id) {
            $expense = $this->expenseRepository->findById(
                $expense_id,
                $organization_id,
            );

            if (!$expense) {
                throw new Exception("Expense not found", 404);
            }

            if ($expense->status !== "pending") {
                throw new Exception(
                    "Only pending expenses can be approved.",
                    403,
                );
            }

            $userId = Auth::id();

            $expense = $this->expenseRepository->update($expense, [
                "status" => "approved",
                "approved_at" => now(),
                "approved_by" => $userId,
            ]);

            return $expense;
        });
    }

    public function rejectExpense($expense_id, $organization_id, $data)
    {
        return DB::transaction(function () use (
            $expense_id,
            $organization_id,
            $data,
        ) {
            $expense = $this->expenseRepository->findById(
                $expense_id,
                $organization_id,
            );

            if (!$expense) {
                throw new Exception("Expense not found", 404);
            }

            if ($expense->status !== "pending") {
                throw new Exception(
                    "Only pending expenses can be rejected.",
                    403,
                );
            }

            $expense = $this->expenseRepository->update($expense, [
                "status" => "rejected",
                "rejected_at" => now(),
                "rejected_reason" => $data["reason"],
            ]);

            return $expense;
        });
    }

    public function stats($organization_id, $filter = "30d")
    {
        [
            $currentStart,
            $currentEnd,
            $prevStart,
            $prevEnd,
            $label,
        ] = $this->parseDateFilter($filter);

        $currentAmount = $this->expenseRepository->getSum(
            $organization_id,
            "approved",
            $currentStart,
            $currentEnd,
        );
        $prevAmount = $this->expenseRepository->getSum(
            $organization_id,
            "approved",
            $prevStart,
            $prevEnd,
        );

        $pendingCount = $this->expenseRepository->getCount(
            $organization_id,
            "pending",
        );
        $approvedCount = $this->expenseRepository->getCount(
            $organization_id,
            "approved",
            $currentStart,
            $currentEnd,
        );

        $budgetStats = $this->budgetRepository->getBudgetStats(
            $organization_id,
            $currentStart->year,
            $currentStart->month,
        );

        $percentChange = 0;
        if ($prevAmount > 0) {
            $percentChange =
                (($currentAmount - $prevAmount) / $prevAmount) * 100;
        } elseif ($currentAmount > 0) {
            $percentChange = 100;
        }

        return [
            "total_expenses" => [
                "amount" => (float) $currentAmount,
                "percent_change" => round($percentChange, 2),
                "trend" => $currentAmount >= $prevAmount ? "up" : "down",
            ],
            "pending_approvals" => [
                "count" => $pendingCount,
            ],
            "approved_expenses" => [
                "count" => $approvedCount,
                "period" => $label,
            ],
            "remaining_budget" => [
                "amount" =>
                    $budgetStats->total_budget -
                    $budgetStats->expenses_sum_amount,
                "allocated" => (float) $budgetStats->total_budget,
            ],
        ];
    }

    public function lineChart($organization_id)
    {
        $year = Carbon::now()->year;
        $chartData = [];

        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create()->month($i)->format("M");
            $startDate = Carbon::create($year, $i, 1)->startOfMonth();
            $endDate = Carbon::create($year, $i, 1)->endOfMonth();

            $expenses = $this->expenseRepository->getSum(
                $organization_id,
                "approved",
                $startDate,
                $endDate,
            );

            $budget = DB::table("budgets")
                ->where("organization_id", $organization_id)
                ->whereYear("month", $year)
                ->whereMonth("month", $i)
                ->sum("amount");

            $chartData[] = [
                "label" => $monthName,
                "expenses" => (float) $expenses,
                "budget" => (float) $budget,
            ];
        }

        return $chartData;
    }

    public function pieChart($organization_id)
    {
        $expenses = DB::table("expenses")
            ->join("categories", "expenses.category_id", "=", "categories.id")
            ->where("expenses.organization_id", $organization_id)
            ->where("expenses.status", "approved")
            ->select(
                "categories.name as label",
                DB::raw("SUM(expenses.amount) as value"),
            )
            ->groupBy("categories.id", "categories.name")
            ->get();

        return $expenses
            ->map(function ($item) {
                return [
                    "label" => ucfirst($item->label),
                    "value" => (float) $item->value,
                ];
            })
            ->toArray();
    }

    private function parseDateFilter($filter)
    {
        $now = Carbon::now();
        $label = "this_period";

        if ($filter === "7d") {
            $start = $now->copy()->subDays(7);
            $end = $now;
            $pStart = $start->copy()->subDays(7);
            $pEnd = $start->copy();
            $label = "last_7_days";
        } elseif ($filter === "last_month") {
            $start = $now->copy()->subMonth()->startOfMonth();
            $end = $now->copy()->subMonth()->endOfMonth();
            $pStart = $start->copy()->subMonth();
            $pEnd = $start->copy()->subDay();
            $label = "last_month";
        } elseif (str_contains($filter, " - ")) {
            // Custom Range: "2026-02-03 - 2026-02-05"
            [$s, $e] = explode(" - ", $filter);
            $start = Carbon::parse($s)->startOfDay();
            $end = Carbon::parse($e)->endOfDay();
            $diffInDays = $start->diffInDays($end);
            $pStart = $start->copy()->subDays($diffInDays);
            $pEnd = $start->copy();
            $label = "custom_range";
        } else {
            // Default 30d
            $start = $now->copy()->subDays(30);
            $end = $now;
            $pStart = $start->copy()->subDays(30);
            $pEnd = $start->copy();
            $label = "last_30_days";
        }

        return [$start, $end, $pStart, $pEnd, $label];
    }
}
