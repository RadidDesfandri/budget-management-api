<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Models\Expense;

class BudgetRepository
{
    public function create(array $data)
    {
        return Budget::create($data);
    }

    public function update(Budget $budget, array $data)
    {
        return $budget->update($data);
    }

    public function findById($id, $organization_id)
    {
        return Budget::where("id", $id)
            ->where("organization_id", $organization_id)
            ->first();
    }

    public function delete(Budget $budget)
    {
        return $budget->delete();
    }

    public function allByOrganizationId(
        $organization_id,
        int $year,
        int $month,
        int $perPage = 10,
    ) {
        return Budget::where("organization_id", $organization_id)
            ->whereYear("month", $year)
            ->whereMonth("month", $month)
            ->with("category")
            ->withSum(
                [
                    "expenses as expenses_sum_amount" => function ($query) {
                        $query->where("status", "approved");
                    },
                ],
                "amount",
            )
            ->paginate($perPage);
    }

    public function existingBudget(
        $month,
        $category_id,
        $organization_id,
        $id = null,
    ) {
        return Budget::where("month", $month)
            ->where("category_id", $category_id)
            ->where("organization_id", $organization_id)
            ->where("id", "!=", $id)
            ->exists();
    }

    public function getBudgetByRange(
        $organization_id,
        $category_id,
        int $year,
        int $month,
    ) {
        return Budget::where("organization_id", $organization_id)
            ->where("category_id", $category_id)
            ->whereYear("month", $year)
            ->whereMonth("month", $month)
            ->first();
    }

    public function getBudgetStats($organization_id, int $year, int $month)
    {
        $totalBudget = Budget::where("organization_id", $organization_id)
            ->whereYear("month", $year)
            ->whereMonth("month", $month)
            ->sum("amount");

        $totalUsed = Expense::whereHas("budget", function ($q) use (
            $organization_id,
            $year,
            $month,
        ) {
            $q->where("organization_id", $organization_id)
                ->whereYear("month", $year)
                ->whereMonth("month", $month);
        })
            ->where("status", "approved")
            ->sum("amount");

        $totalPending = Expense::whereHas("budget", function ($q) use (
            $organization_id,
            $year,
            $month,
        ) {
            $q->where("organization_id", $organization_id)
                ->whereYear("month", $year)
                ->whereMonth("month", $month);
        })
            ->where("status", "pending")
            ->sum("amount");

        return (object) [
            "total_budget" => $totalBudget,
            "expenses_sum_amount" => $totalUsed,
            "total_pending" => $totalPending,
        ];
    }
}
