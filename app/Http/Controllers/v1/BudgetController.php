<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\BudgetService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function __construct(protected BudgetService $budgetService) {}

    public function create(Request $request, $organization_id)
    {
        $monthDate = $request->input("month")
            ? $request->input("month") . "-01"
            : null;

        $validated = $request->validate([
            "amount" => "required|numeric|min:0",
            "month" => ["required", "date_format:Y-m"],
            "category_id" => [
                "required",
                "integer",
                Rule::exists("categories", "id")->where(
                    "organization_id",
                    $organization_id,
                ),
            ],
        ]);

        try {
            $budget = $this->budgetService->create([
                "amount" => $validated["amount"],
                "month" => $monthDate,
                "category_id" => $validated["category_id"],
                "organization_id" => $organization_id,
            ]);

            return $this->successResponse(
                "Budget created successfully",
                $budget,
                201,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function update(Request $request, $organization_id, $id)
    {
        $monthDate = $request->input("month")
            ? $request->input("month") . "-01"
            : null;

        $validated = $request->validate([
            "amount" => "required|numeric|min:0",
            "month" => ["required", "date_format:Y-m"],
            "category_id" => [
                "required",
                "integer",
                Rule::exists("categories", "id")->where(
                    "organization_id",
                    $organization_id,
                ),
            ],
        ]);

        try {
            $budget = $this->budgetService->update(
                $id,
                [
                    "amount" => $validated["amount"],
                    "month" => $monthDate,
                    "category_id" => $validated["category_id"],
                ],
                $organization_id,
            );

            return $this->successResponse(
                "Budget updated successfully",
                $budget,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function delete($organization_id, $id)
    {
        try {
            $this->budgetService->delete($id, $organization_id);

            return $this->successResponse(
                "Budget deleted successfully",
                null,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function allByOrganization(Request $request, $organization_id)
    {
        $validated = $request->validate([
            "period" => "nullable|date_format:Y-m",
            "sort_by" => "nullable|in:budget,used,remaining",
            "order_by" => "nullable|in:asc,desc",
            "page_size" => "nullable|integer|min:1|max:100",
        ]);

        $budgets = $this->budgetService->allByOrganization(
            $organization_id,
            $validated,
        );

        return $this->successResponse(
            "Budgets retrieved successfully",
            $budgets,
        );
    }

    public function show($organization_id, $id)
    {
        try {
            $budget = $this->budgetService->findById($id, $organization_id);

            return $this->successResponse(
                "Budget retrieved successfully",
                $budget,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }
}
