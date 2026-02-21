<?php

namespace App\Repositories;

use App\Models\Organization;

class OrganizationRepository
{
    public function all()
    {
        return Organization::all();
    }

    public function find($id)
    {
        return Organization::find($id);
    }

    public function create(array $data)
    {
        return Organization::create($data);
    }

    public function update(Organization $organization, array $data)
    {
        $organization->update($data);
        return $organization;
    }

    public function delete(Organization $organization)
    {
        $organization->delete();
    }
}
