<?php

namespace App\Repositories;

use App\Models\Invitation;

class InvitationRepository
{
    public function create(array $data)
    {
        return Invitation::create($data);
    }

    public function model()
    {
        return Invitation::class;
    }
}
