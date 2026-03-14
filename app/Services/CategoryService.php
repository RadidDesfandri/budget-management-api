<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Repositories\ExpenseRepository;
use Exception;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected ExpenseRepository $expenseRepository,
    ) {}

    public function create(array $data)
    {
        return $this->categoryRepository->create($data);
    }

    public function update($id, array $data, $organizationId)
    {
        $category = $this->categoryRepository->findById($id, $organizationId);

        if (!$category) {
            throw new Exception("Category not found", 404);
        }

        return $this->categoryRepository->update($category, $data);
    }

    public function delete($id, $organizationId)
    {
        $category = $this->categoryRepository->findById($id, $organizationId);

        if (!$category) {
            throw new Exception("Category not found", 404);
        }

        return $this->categoryRepository->delete($category);
    }

    public function categoriesOfOrganization(
        $organization_id,
        array $filters = [],
    ) {
        $paginator = $this->categoryRepository->paginateByOrganizationId(
            $organization_id,
            $filters,
        );

        $categoryIds = $paginator
            ->getCollection()
            ->pluck("id")
            ->values()
            ->all();

        $recentByCategory = $this->expenseRepository->recentExpensesByCategoryIds(
            $organization_id,
            $categoryIds,
            5,
        );

        $paginator->setCollection(
            $paginator
                ->getCollection()
                ->map(function ($category) use ($recentByCategory) {
                    $category->setAttribute(
                        "recent_expenses",
                        $recentByCategory[$category->id] ?? [],
                    );
                    return $category;
                }),
        );

        $mostActive = $this->categoryRepository->mostActiveByOrganizationId(
            $organization_id,
        );
        $mostActivePayload = $mostActive
            ? [
                "id" => $mostActive->id,
                "name" => $mostActive->name,
                "expenses_count" => (int) $mostActive->expenses_count,
            ]
            : null;

        return [
            "most_active_category" => $mostActivePayload,
            "categories" => $paginator,
        ];
    }
}
