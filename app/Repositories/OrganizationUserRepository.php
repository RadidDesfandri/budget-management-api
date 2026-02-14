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

    public function memberList($user)
    {
        return OrganizationUser::with('user:id,name,email')
            ->where('organization_id', $user->current_organization_id)
            ->get();
    }
}
