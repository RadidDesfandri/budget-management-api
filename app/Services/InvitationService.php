<?php

namespace App\Services;

use App\Mail\InvitationMail;
use App\Repositories\InvitationRepository;
use App\Repositories\OrganizationUserRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationService
{
    public function __construct(
        protected InvitationRepository $invitationRepo,
        protected OrganizationUserRepository $organizationUserRepo
    ) {}

    public function createInvitation(array $data, $organizationId, $user)
    {
        if ($this->organizationUserRepo->isMember($data['email'], $organizationId)) {
            throw new Exception('User with this email is already a member.', 400);
        }

        if ($this->invitationRepo->hasActiveInvitation($data['email'], $organizationId)) {
            throw new Exception('An active invitation already exists for this email.', 400);
        }

        return DB::transaction(function () use ($data, $organizationId, $user) {
            $token = Str::random(64);

            $invitation = $this->invitationRepo->create([
                'email' => $data['email'],
                'role' => $data['role'],
                'organization_id' => $organizationId,
                'token' => $token,
                'invited_by' => $user->id,
                'expires_at' => Carbon::now()->addDays(7),
            ]);

            $organization = $user->organizations()->where('organizations.id', $organizationId)->first();
            $frontendUrl = config('app.frontend_url') . '/invitation/accept?token=' . $token;

            Mail::to($data['email'])->send(new InvitationMail($invitation, $organization, $frontendUrl));

            return $invitation;
        });
    }

    public function verifyTokenInvitation(string $token)
    {
        $invitation = $this->invitationRepo->findByToken($token);

        if (!$invitation) {
            throw new Exception('This invitation does not exist', 404);
        }

        if ($invitation->accepted_at) {
            throw new Exception('This invitation has already been accepted', 400);
        }

        if ($invitation->rejected_at) {
            throw new Exception('This invitation has already been rejected', 400);
        }

        if (Carbon::now()->greaterThan($invitation->expires_at)) {
            throw new Exception('This invitation has expired', 400);
        }

        return $invitation;
    }

    public function acceptInvitation($invitation, $user)
    {
        return DB::transaction(function () use ($invitation, $user) {
            if ($invitation->email !== $user->email) {
                throw new Exception('This invitation is not for your email address', 403);
            }

            $isMember = $this->organizationUserRepo->isMemberByUserId($user->id, $invitation->organization_id);

            if ($isMember) {
                $this->invitationRepo->update($invitation, [
                    'accepted_at' => Carbon::now()
                ]);

                return $invitation;
            }

            $this->invitationRepo->update($invitation, [
                'accepted_at' => Carbon::now()
            ]);

            $this->organizationUserRepo->addUser([
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
                'role' => $invitation->role,
                'joined_at' => Carbon::now(),
            ]);

            return $invitation;
        });
    }

    public function rejectInvitation($invitation, $user)
    {
        return DB::transaction(function () use ($invitation, $user) {
            if ($invitation->email !== $user->email) {
                throw new Exception('This invitation is not for your email address', 403);
            }

            $isMember = $this->organizationUserRepo->isMemberByUserId($user->id, $invitation->organization_id);

            if ($isMember) {
                throw new Exception('You are already a member of this organization', 400);
            }

            $this->invitationRepo->update($invitation, [
                'rejected_at' => Carbon::now()
            ]);

            return $invitation;
        });
    }
}
