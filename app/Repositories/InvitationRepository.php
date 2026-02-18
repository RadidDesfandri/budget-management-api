<?php

namespace App\Repositories;

use App\Models\Invitation;
use Illuminate\Support\Carbon;

class InvitationRepository
{
    public function create(array $data)
    {
        return Invitation::create($data);
    }

    public function update(Invitation $invitation, array $data)
    {
        $invitation->update($data);
    }

    public function findByToken(string $token)
    {
        return Invitation::with(['organization:id,name,logo_url', 'invitedBy:id,name'])
            ->where('token', $token)
            ->first();
    }

    public function hasActiveInvitation(string $email, $organizationId): bool
    {
        return Invitation::where('email', $email)
            ->where('organization_id', $organizationId)
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }
}
