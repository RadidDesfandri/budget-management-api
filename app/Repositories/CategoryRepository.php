<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    public function create(array $data)
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data)
    {
        return $category->update($data);
    }

    public function findById($id, $organization_id)
    {
        return Category::where("id", $id)
            ->where("organization_id", $organization_id)
            ->first();
    }

    public function delete(Category $category)
    {
        return $category->delete();
    }

    public function paginateByOrganizationId(
        $organization_id,
        array $filters = [],
    ) {
        $query = Category::query()
            ->where("organization_id", $organization_id)
            ->with(["createdBy:id,name"])
            ->withCount("expenses");

        if (isset($filters["search"])) {
            $query->whereRaw("LOWER(name) LIKE ?", [
                "%" . strtolower($filters["search"]) . "%",
            ]);
        }

        $allowedSortBy = [
            "name" => "name",
            "created_at" => "created_at",
            "expenses_count" => "expenses_count",
        ];

        $sortBy = $filters["sort_by"] ?? "created_at";
        $sortBy = $allowedSortBy[$sortBy] ?? "created_at";

        $orderBy = strtolower($filters["order_by"] ?? "desc");
        $orderBy = in_array($orderBy, ["asc", "desc"], true)
            ? $orderBy
            : "desc";

        $perPage = (int) ($filters["page_size"] ?? 10);
        $perPage = max(1, min($perPage, 100));

        return $query->orderBy($sortBy, $orderBy)->paginate($perPage);
    }

    public function mostActiveByOrganizationId($organization_id)
    {
        return Category::query()
            ->where("organization_id", $organization_id)
            ->withCount("expenses")
            ->orderByDesc("expenses_count")
            ->first(["id", "name"]);
    }
}
