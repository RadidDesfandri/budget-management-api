<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService) {}

    public function create(Request $request, $organization_id)
    {
        $validated = $request->validate([
            "name" =>
                "required|string|max:255|unique:categories,name,NULL,id,organization_id," .
                $organization_id,
            "icon" => "required|string|max:255",
            "icon_color" => "nullable|string|max:255",
            "background_color" => "nullable|string|max:255",
        ]);

        $category = $this->categoryService->create([
            "name" => $validated["name"],
            "organization_id" => $organization_id,
            "icon" => $validated["icon"],
            "icon_color" => $validated["icon_color"] ?? "#36454f",
            "background_color" => $validated["background_color"] ?? "#ade8f4",
            "created_by" => $request->user()->id,
        ]);

        return $this->successResponse(
            "Category created successfully",
            $category,
            201,
        );
    }

    public function update(Request $request, $organization_id, $id)
    {
        $validated = $request->validate([
            "name" => [
                "sometimes",
                "string",
                "max:255",
                Rule::unique("categories")
                    ->where("organization_id", $organization_id)
                    ->ignore($id),
            ],
            $organization_id,
            "icon" => "sometimes|string|max:255",
            "icon_color" => "sometimes|string|max:255",
            "background_color" => "sometimes|string|max:255",
        ]);

        try {
            $category = $this->categoryService->update(
                $id,
                $validated,
                $organization_id,
            );

            return $this->successResponse(
                "Category updated successfully",
                $category,
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
            $category = $this->categoryService->delete($id, $organization_id);

            return $this->successResponse(
                "Category deleted successfully",
                $category,
                200,
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = $code >= 200 && $code <= 599 ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function categoriesOfOrganization(Request $request, $organization_id)
    {
        $filters = $request->validate([
            "page" => "sometimes|integer|min:1",
            "per_page" => "sometimes|integer|min:1|max:100",
            "sort_by" => "sometimes|string|in:name,created_at,expenses_count",
            "order_by" => "sometimes|string|in:asc,desc",
            "search" => "sometimes|string|max:255",
        ]);

        $categories = $this->categoryService->categoriesOfOrganization(
            $organization_id,
            $filters,
        );

        return $this->successResponse(
            "Categories retrieved successfully",
            $categories,
        );
    }
}
