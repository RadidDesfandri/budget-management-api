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

            if (!$budget) {
                throw new Exception(
                    "There is no budget for this category in the period " .
                        $date->format("F Y"),
                    404,
                );
            }

            $data["status"] = "pending";
            $data["budget_id"] = $budget->id;

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
    ) {
        return DB::transaction(function () use (
            $expense_id,
            $organization_id,
            $data,
            $receipt,
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
