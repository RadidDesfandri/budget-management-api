<?php

namespace App\Repositories;

use App\Models\OrganizationUser;

class OrganizationUserRepository
{
    public function all()
    {
        return OrganizationUser::all();
    }

    public function find($id)
    {
        return OrganizationUser::find($id);
    }

    public function addUser(array $data)
    {
        return OrganizationUser::create($data);
    }
}
