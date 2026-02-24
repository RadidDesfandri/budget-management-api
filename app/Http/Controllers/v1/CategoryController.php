<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Exception;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService) {}

    public function create(Request $request, $organization_id)
    {
        $validated = $request->validate([
            "name" =>
                "required|string|max:255|unique:categories,name,NULL,id,organization_id," .
                $organization_id,
        ]);

        $category = $this->categoryService->create([
            "name" => $validated["name"],
            "organization_id" => $organization_id,
        ]);

        return $this->successResponse(
            "Category created successfully",
            $category,
            201,
        );
    }

    public function update(Request $request, $organization_id, $id)
    {
        try {
            $validated = $request->validate([
                "name" =>
                    "required|string|max:255|unique:categories,name,NULL,id,organization_id," .
                    $organization_id,
            ]);

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

    public function allByOrganization($organization_id)
    {
        $categories = $this->categoryService->allByOrganization(
            $organization_id,
        );

        return $this->successResponse(
            "Categories retrieved successfully",
            $categories,
        );
    }
}
