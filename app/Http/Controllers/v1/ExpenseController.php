<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\ExpenseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $expenseService) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            "user_id",
            "status",
            "category_id",
            "budget_id",
            "date_from",
            "date_to",
        ]);

        // $expenses = $this->expenseService->getExpenses($filters);

        return $this->successResponse("Expenses fetched successfully", [], 200);
    }

    public function store(Request $request, $organization_id)
    {
        $data = $request->validate([
            "title" => "required|string|max:255",
            "amount" => "required|numeric|min:0.01",
            "description" => "required|string|max:500",
            "expense_date" => "required|date|before_or_equal:today",
            "category_id" => [
                "required",
                "integer",
                Rule::exists("categories", "id")->where(
                    "organization_id",
                    $organization_id,
                ),
            ],
            "receipt" => "required|file|mimes:jpg,jpeg,png,pdf|max:5120", // 5MB
        ]);

        try {
            $data["user_id"] = $request->user()->id;
            $data["organization_id"] = $organization_id;

            $expense = $this->expenseService->createExpense(
                data: $data,
                receipt: $request->file("receipt"),
            );

            return $this->successResponse(
                "Expense created successfully",
                $expense,
                201,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function update(Request $request, $organization_id, $expense_id)
    {
        $data = $request->validate([
            "title" => "sometimes|string|max:255",
            "amount" => "sometimes|numeric|min:0.01",
            "description" => "sometimes|string|max:500",
            "expense_date" => "sometimes|date|before_or_equal:today",
            "category_id" => [
                "sometimes",
                "integer",
                Rule::exists("categories", "id")->where(
                    "organization_id",
                    $organization_id,
                ),
            ],
            "receipt" => "nullable|file|mimes:jpg,jpeg,png,pdf|max:5120", // 5MB
        ]);

        try {
            $expense = $this->expenseService->updateExpense(
                expense_id: $expense_id,
                organization_id: $organization_id,
                data: $data,
                receipt: $request->file("receipt"),
            );

            return $this->successResponse(
                "Expense updated successfully",
                $expense,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function destroy($organization_id, $expense_id)
    {
        try {
            $expense = $this->expenseService->deleteExpense(
                $expense_id,
                $organization_id,
            );

            return $this->successResponse(
                "Expense deleted successfully",
                $expense,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }
}
