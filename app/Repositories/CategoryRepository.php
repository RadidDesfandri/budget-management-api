<?php

namespace App\Repositories;

use App\Models\Category;

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

    public function allByOrganizationId($organization_id)
    {
        return Category::where("organization_id", $organization_id)->get();
    }
}
