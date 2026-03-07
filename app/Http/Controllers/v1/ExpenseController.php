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

    public function index(Request $request, $organization_id)
    {
        $filters = $request->only([
            "status",
            "category",
            "date_from",
            "date_to",
            "search",
            "sort_by",
            "order_by",
            "page_size",
        ]);

        $user = $request->user();
        $organization = $user
            ->organizations()
            ->where("organizations.id", $organization_id)
            ->first();

        $role = $organization->pivot->role;

        $expenses = $this->expenseService->getExpenses(
            $filters,
            $organization_id,
            $user->id,
            $role,
        );

        return $this->successResponse(
            "Expenses fetched successfully",
            $expenses,
            200,
        );
    }

    public function show($organization_id, $expense_id)
    {
        try {
            $expense = $this->expenseService->getExpense(
                $expense_id,
                $organization_id,
            );

            return $this->successResponse(
                "Expense fetched successfully",
                $expense,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
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
            $user = $request->user();
            $organization = $user
                ->organizations()
                ->where("organizations.id", $organization_id)
                ->first();

            $role = $organization->pivot->role;

            $expense = $this->expenseService->updateExpense(
                expense_id: $expense_id,
                organization_id: $organization_id,
                data: $data,
                receipt: $request->file("receipt"),
                user_id: $user->id,
                role: $role,
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

    public function approve($organization_id, $expense_id)
    {
        try {
            $expense = $this->expenseService->approveExpense(
                $expense_id,
                $organization_id,
            );

            return $this->successResponse(
                "Expense approved successfully",
                $expense,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function reject(Request $request, $organization_id, $expense_id)
    {
        $data = $request->validate([
            "reason" => "required|string|max:500|min:10",
        ]);

        try {
            $expense = $this->expenseService->rejectExpense(
                $expense_id,
                $organization_id,
                $data,
            );

            return $this->successResponse(
                "Expense rejected successfully",
                $expense,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function stats(Request $request, $organization_id)
    {
        try {
            $filter = $request->query("filter", "30d");
            $stats = $this->expenseService->stats($organization_id, $filter);

            return $this->successResponse(
                "Expense stats fetched successfully",
                $stats,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function lineChart($organization_id)
    {
        try {
            $lineChart = $this->expenseService->lineChart($organization_id);

            return $this->successResponse(
                "Expense line chart fetched successfully",
                $lineChart,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function pieChart($organization_id)
    {
        try {
            $pieChart = $this->expenseService->pieChart($organization_id);

            return $this->successResponse(
                "Expense pie chart fetched successfully",
                $pieChart,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }
}
