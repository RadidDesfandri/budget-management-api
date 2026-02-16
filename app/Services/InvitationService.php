<?php

namespace App\Services;

use App\Repositories\InvitationRepository;
use App\Repositories\OrganizationUserRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InvitationService
{
    public function __construct(
        protected InvitationRepository $invitationRepo,
        protected OrganizationUserRepository $organizationUserRepo
    ) {}

    public function createInvitation(array $data, $organizationId)
    {
        $email = $data['email'];
        $role = $data['role'];

        $token = Str::random(64);

        $invitation = $this->invitationRepo->create([
            'email' => $email,
            'role' => $role,
            'organization_id' => $organizationId,
            'token' => $token,
            'invited_by' => Auth::id(),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        return $invitation;
    }

    public function ensureEmailNotMember($email, $organizationId)
    {
        return $this->organizationUserRepo->model()::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->where('organization_id', $organizationId)->exists();
    }

    public function ensureNoActiveInvitation($email, $organizationId)
    {
        return $this->invitationRepo->model()::where('email', $email)
            ->where('organization_id', $organizationId)
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }
}
