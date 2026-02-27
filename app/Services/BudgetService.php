<?php

namespace App\Services;

use App\Repositories\BudgetRepository;
use Exception;

class BudgetService
{
    public function __construct(protected BudgetRepository $budgetRepository) {}

    public function create(array $data)
    {
        $existingBudget = $this->budgetRepository->existingBudget(
            $data["month"],
            $data["category_id"],
            $data["organization_id"],
        );

        if ($existingBudget) {
            throw new Exception(
                "The budget for this category and month is already available.",
                400,
            );
        }

        return $this->budgetRepository->create($data);
    }

    public function update($id, array $data, $organizationId)
    {
        $budget = $this->budgetRepository->findById($id, $organizationId);

        if (!$budget) {
            throw new Exception("Budget not found", 404);
        }

        $existingBudget = $this->budgetRepository->existingBudget(
            $data["month"],
            $data["category_id"],
            $organizationId,
            $id,
        );
        if ($existingBudget) {
            throw new Exception(
                "The budget for this category and month is already available.",
                400,
            );
        }

        $this->budgetRepository->update($budget, $data);

        return $budget;
    }

    public function delete($id, $organizationId)
    {
        $budget = $this->budgetRepository->findById($id, $organizationId);

        if (!$budget) {
            throw new Exception("Budget not found", 404);
        }

        return $this->budgetRepository->delete($budget);
    }

    public function findById($id, $organizationId)
    {
        $budget = $this->budgetRepository->findById($id, $organizationId);

        if (!$budget) {
            throw new Exception("Budget not found", 404);
        }

        return $budget->load("category");
    }

    public function allByOrganization($organization_id, $filters)
    {
        $period = $filters["period"] ?? now()->format("Y-m");
        $sortBy = $filters["sort_by"] ?? "budget";
        $sortDir = $filters["order_by"] ?? "desc";
        $perPage = $filters["page_size"] ?? 10;

        [$year, $month] = explode("-", $period);

        $budgets = $this->budgetRepository->allByOrganizationId(
            $organization_id,
            (int) $year,
            (int) $month,
            $perPage,
        );

        $formatted = $budgets->through(function ($budget) {
            $used = $budget->expenses_sum_amount ?? 0; // dummy: ganti saat expenses sudah siap
            $remaining = $budget->amount - $used;

            return [
                "id" => $budget->id,
                "budget" => $budget->amount,
                "used" => $used,
                "remaining" => $remaining,
                "category" => [
                    "id" => $budget->category->id,
                    "name" => $budget->category->name,
                    "icon" => $budget->category->icon ?? null,
                    "icon_color" => $budget->category->icon_color ?? null,
                    "background_color" =>
                        $budget->category->background_color ?? null,
                ],
            ];
        });

        $sorted = collect($formatted->items())
            ->sortBy($sortBy, SORT_REGULAR, $sortDir === "desc")
            ->values();

        $totalBudget = $this->budgetRepository->sumByOrganizationId(
            $organization_id,
            (int) $year,
            (int) $month,
        );
        // $totalUsed = $budgets->sum(fn($b) => $b->expenses_sum_amount ?? 0);
        $totalUsed = 0;

        return [
            "period" => $period,
            "total_budget" => $totalBudget,
            "total_used" => $totalUsed,
            "total_remaining" => $totalBudget - $totalUsed,
            "budgets" => [
                "data" => $sorted,
                "current_page" => $budgets->currentPage(),
                "per_page" => $budgets->perPage(),
                "total" => $budgets->total(),
                "last_page" => $budgets->lastPage(),
            ],
        ];
    }
}
