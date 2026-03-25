<?php

namespace App\Services;

use App\Repositories\BudgetRepository;
use App\Repositories\ExpenseRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function __construct(
        protected BudgetRepository $budgetRepository,
        protected ExpenseRepository $expenseRepository,
        protected AuditTrailService $auditTrailService,
    ) {}

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $existingBudget = $this->budgetRepository->existingBudget(
                $data["month"],
                $data["category_id"],
                $data["organization_id"],
            );

            if ($existingBudget) {
                throw new Exception(
                    "The budget for this category and month is already available.",
                    400,
                );
            }

            $budget = $this->budgetRepository->create($data);

            $date = Carbon::parse($data["month"]);
            $this->expenseRepository->assignBudgetToUnassignedExpenses(
                $budget->id,
                $data["organization_id"],
                $data["category_id"],
                $date->year,
                $date->month,
            );

            $this->auditTrailService->logFromRequest(
                request: request(),
                organizationId: (int) $data["organization_id"],
                actionType: "budget_created",
                description: "Created a new budget for \"{$budget->category->name}\" with amount Rp " .
                    number_format($data["amount"], 0, ",", "."),
                metadata: [
                    "budget_id" => $budget->id,
                    "category_id" => $data["category_id"],
                    "amount" => $data["amount"],
                    "month" => $data["month"],
                ],
            );

            return $budget->load("category");
        });
    }

    public function update($id, array $data, $organizationId)
    {
        $budget = $this->budgetRepository->findById($id, $organizationId);

        if (!$budget) {
            throw new Exception("Budget not found", 404);
        }

        $existingBudget = $this->budgetRepository->existingBudget(
            $data["month"],
            $data["category_id"],
            $organizationId,
            $id,
        );
        if ($existingBudget) {
            throw new Exception(
                "The budget for this category and month is already available.",
                400,
            );
        }

        $oldAmount = $budget->amount;
        $oldMonth = $budget->month_formatted;
        $oldCategoryId = $budget->category_id;

        $this->budgetRepository->update($budget, $data);

        $changes = [];
        if (isset($data["amount"]) && $data["amount"] != $oldAmount) {
            $changes[] =
                "changed amount to Rp " .
                number_format($data["amount"], 0, ",", ".");
        }
        if (
            isset($data["month"]) &&
            rtrim($data["month"], "-01") !== rtrim($oldMonth, "-01")
        ) {
            $changes[] = "changed month to " . rtrim($data["month"], "-01");
        }
        if (
            isset($data["category_id"]) &&
            $data["category_id"] != $oldCategoryId
        ) {
            $changes[] = "changed category to ID {$data["category_id"]}";
        }

        if (!empty($changes)) {
            $descriptionSuffix = " — " . implode(", ", $changes);
            $this->auditTrailService->logFromRequest(
                request: request(),
                organizationId: (int) $organizationId,
                actionType: "budget_updated",
                description: "Updated budget (ID: {$id}){$descriptionSuffix}",
                metadata: array_merge(["budget_id" => (int) $id], $data),
            );
        }

        return $budget;
    }

    public function delete($id, $organizationId)
    {
        $budget = $this->budgetRepository->findById($id, $organizationId);

        if (!$budget) {
            throw new Exception("Budget not found", 404);
        }

        $result = $this->budgetRepository->delete($budget);

        $this->auditTrailService->logFromRequest(
            request: request(),
            organizationId: (int) $organizationId,
            actionType: "budget_deleted",
            description: "Deleted budget (ID: {$id})",
            metadata: [
                "budget_id" => (int) $id,
            ],
        );

        return $result;
    }

    public function findById($id, $organizationId)
    {
        $budget = $this->budgetRepository->findById($id, $organizationId);

        if (!$budget) {
            throw new Exception("Budget not found", 404);
        }

        return $budget->load("category");
    }

    public function getBudgets($organization_id, $filters)
    {
        $period = $filters["period"] ?? now()->format("Y-m");
        $sortBy = $filters["sort_by"] ?? "budget";
        $sortDir = $filters["order_by"] ?? "desc";
        $perPage = $filters["page_size"] ?? 10;

        [$year, $month] = explode("-", $period);

        $budgets = $this->budgetRepository->allByOrganizationId(
            $organization_id,
            (int) $year,
            (int) $month,
            $perPage,
        );

        $formatted = $budgets->through(function ($budget) {
            $used = $budget->expenses_sum_amount ?? 0;
            $remaining = $budget->amount - $used;

            return [
                "id" => $budget->id,
                "budget" => $budget->amount,
                "used" => $used,
                "remaining" => $remaining,
                "month" => $budget->month_formatted,
                "updated_at" => $budget->updated_at,
                "expenses" => $budget->expenses,
                "category" => $budget->category,
                "created_by" => $budget->createdBy->name,
            ];
        });

        $sorted = collect($formatted->items())
            ->sortBy($sortBy, SORT_REGULAR, $sortDir === "desc")
            ->values();

        $stats = $this->budgetRepository->getBudgetStats(
            $organization_id,
            (int) $year,
            (int) $month,
        );

        $totalBudget = $stats->total_budget ?? 0;
        $totalUsed = $stats->expenses_sum_amount ?? 0;
        $totalPending = $stats->total_pending ?? 0;

        return [
            "period" => $period,
            "total_budget" => $totalBudget,
            "total_used" => $totalUsed,
            "total_remaining" => $totalBudget - $totalUsed,
            "total_pending" => $totalPending,
            "budgets" => [
                "data" => $sorted,
                "current_page" => $budgets->currentPage(),
                "per_page" => $budgets->perPage(),
                "total" => $budgets->total(),
                "last_page" => $budgets->lastPage(),
            ],
        ];
    }
}
