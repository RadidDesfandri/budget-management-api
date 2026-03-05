<?php

namespace App\Services;

use App\Repositories\BudgetRepository;
use App\Repositories\ExpenseRepository;
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
}
