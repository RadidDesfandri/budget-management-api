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

    public function countPendingInvitations($organizationId): int
    {
        return Invitation::where('organization_id', $organizationId)
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>', Carbon::now())
            ->count();
    }

    public function getPaginatedInvitations($user, array $filters)
    {
        $query = Invitation::with(['organization:id,name,logo_url', 'invitedBy:id,name'])
            ->where('email', $user->email);

        $status = $filters['status'];

        switch ($status) {
            case 'pending':
                $query->whereNull('accepted_at')
                    ->whereNull('rejected_at')
                    ->where('expires_at', '>', Carbon::now());
                break;

            case 'accepted':
                $query->whereNotNull('accepted_at');
                break;

            case 'rejected':
                $query->whereNotNull('rejected_at');
                break;

            case 'expired':
                $query->where('expires_at', '<', Carbon::now())
                    ->whereNull('accepted_at')
                    ->whereNull('rejected_at');
                break;

            case 'history':
                $query->where(function ($q) {
                    $q->whereNotNull('accepted_at')
                        ->orWhereNotNull('rejected_at')
                        ->orWhere('expires_at', '<', Carbon::now());
                });
                break;

            default:
                $query->whereNotNull($status);
                break;
        }

        return $query->paginate($filters['page_size']);
    }
}
