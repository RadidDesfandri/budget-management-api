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

    public function paginate($organization_id, $filters, $user_id, $role)
    {
        $query = Expense::where("organization_id", $organization_id)->with([
            "category:id,name,icon,icon_color,background_color",
            "user:id,name,avatar_url",
        ]);

        if ($role === "member") {
            $query->where("user_id", $user_id);
        }

        if (isset($filters["category"])) {
            $query->whereHas("category", function ($query) use ($filters) {
                $query->where("name", "like", "%" . $filters["category"] . "%");
            });
        }

        if (isset($filters["date_from"]) && isset($filters["date_to"])) {
            $query->whereBetween("expense_date", [
                $filters["date_from"],
                $filters["date_to"],
            ]);
        }

        if (isset($filters["status"])) {
            $query->where("status", $filters["status"]);
        }

        if (isset($filters["search"])) {
            $query->where(function ($q) use ($filters) {
                $searchTerm = "%" . $filters["search"] . "%";
                $q->where("title", "like", $searchTerm)
                    ->orWhere("amount", "like", $searchTerm)
                    ->orWhere("description", "like", $searchTerm)
                    ->orWhereHas("user", function ($inner) use ($searchTerm) {
                        $inner->where("name", "like", $searchTerm);
                    });
            });
        }

        $sortBy = $filters["sort_by"] ?? "expense_date";
        $orderBy = $filters["order_by"] ?? "desc";
        $pageSize = $filters["page_size"] ?? 10;

        return $query->orderBy($sortBy, $orderBy)->paginate($pageSize);
    }
}
