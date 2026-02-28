<?php

namespace App\Repositories;

use App\Models\Expense;

class ExpenseRepository
{
    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        return $expense->fresh();
    }

    public function delete(Expense $expense): bool
    {
        return $expense->delete();
    }

    public function findById($expense_id, $organization_id)
    {
        return Expense::where("id", $expense_id)
            ->where("organization_id", $organization_id)
            ->first();
    }

    public function paginate($organization_id, $filters, int $perPage = 10)
    {
        $query = Expense::where("organization_id", $organization_id)->with([
            "category",
            "user",
        ]);

        if (isset($filters["category_id"])) {
            $query->where("category_id", $filters["category_id"]);
        }

        if (isset($filters["start_date"]) && isset($filters["end_date"])) {
            $query->whereBetween("expense_date", [
                $filters["start_date"],
                $filters["end_date"],
            ]);
        }

        if (isset($filters["status"])) {
            $query->where("status", $filters["status"]);
        }

        if (isset($filters["search"])) {
            $query->where("title", "like", "%" . $filters["search"] . "%");
        }

        $sortBy = $filters["sort_by"] ?? "expense_date";
        $orderBy = $filters["order_by"] ?? "desc";

        $query->orderBy($sortBy, $orderBy);

        return $query->paginate($perPage);
    }
}
