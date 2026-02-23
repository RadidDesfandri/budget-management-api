<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Exception;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
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

    public function allByOrganization($organization_id)
    {
        return $this->categoryRepository->allByOrganizationId($organization_id);
    }
}
