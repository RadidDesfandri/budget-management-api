<?php

namespace App\Services;

use App\Repositories\ExpenseRepository;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        protected ExpenseRepository $expenseRepository,
        protected FileStorageService $fileStorageService,
    ) {}

    public function createExpense(array $data, ?UploadedFile $receipt = null)
    {
        return DB::transaction(function () use ($data, $receipt) {
            $data["status"] = "pending";

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
