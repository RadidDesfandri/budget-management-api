<?php

namespace App\Repositories;

use App\Models\Budget;

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
            // ->withSum("expenses", "amount")
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
}
