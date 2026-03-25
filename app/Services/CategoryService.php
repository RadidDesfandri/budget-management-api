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
        protected AuditTrailService $auditTrailService,
    ) {}

    public function create(array $data)
    {
        $category = $this->categoryRepository->create($data);

        $this->auditTrailService->logFromRequest(
            request: request(),
            organizationId: (int) $data["organization_id"],
            actionType: "category_created",
            description: "Created new expense category \"{$data["name"]}\"",
            metadata: [
                "category_id" => $category->id,
                "name" => $data["name"],
            ],
        );

        return $category;
    }

    public function update($id, array $data, $organizationId)
    {
        $category = $this->categoryRepository->findById($id, $organizationId);

        if (!$category) {
            throw new Exception("Category not found", 404);
        }

        $oldName = $category->name;
        $oldIcon = $category->icon;
        $oldIconColor = $category->icon_color;
        $oldBgColor = $category->background_color;

        $result = $this->categoryRepository->update($category, $data);

        $changes = [];
        if (isset($data["name"]) && $data["name"] !== $oldName) {
            $changes[] = "renamed to \"{$data["name"]}\"";
        }
        if (isset($data["icon"]) && $data["icon"] !== $oldIcon) {
            $changes[] = "changed icon to \"{$data["icon"]}\"";
        }
        if (
            isset($data["icon_color"]) &&
            $data["icon_color"] !== $oldIconColor
        ) {
            $changes[] = "changed icon color to \"{$data["icon_color"]}\"";
        }
        if (
            isset($data["background_color"]) &&
            $data["background_color"] !== $oldBgColor
        ) {
            $changes[] = "changed background color to \"{$data["background_color"]}\"";
        }

        if (!empty($changes)) {
            $descriptionSuffix = !empty($changes)
                ? " — " . implode(", ", $changes)
                : "";
            $this->auditTrailService->logFromRequest(
                request: request(),
                organizationId: (int) $organizationId,
                actionType: "category_updated",
                description: "Updated category \"{$oldName}\" {$descriptionSuffix}",
                metadata: array_merge(["category_id" => (int) $id], $data),
            );
        }

        return $result;
    }

    public function delete($id, $organizationId)
    {
        $category = $this->categoryRepository->findById($id, $organizationId);

        if (!$category) {
            throw new Exception("Category not found", 404);
        }

        $name = $category->name;
        $result = $this->categoryRepository->delete($category);

        $this->auditTrailService->logFromRequest(
            request: request(),
            organizationId: (int) $organizationId,
            actionType: "category_deleted",
            description: "Deleted unused category (ID: {$id}) \"{$name}\"",
            metadata: [
                "category_id" => (int) $id,
                "name" => $name,
            ],
        );

        return $result;
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
