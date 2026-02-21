<?php

namespace App\Services;

use App\Models\OrganizationUser;
use App\Repositories\InvitationRepository;
use App\Repositories\OrganizationRepository;
use App\Repositories\OrganizationUserRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    public function __construct(
        protected OrganizationRepository $organizationRepo,
        protected OrganizationUserRepository $organizationUserRepo,
        protected FileStorageService $fileStorage,
        protected InvitationRepository $invitationRepo,
    ) {}

    public function detail($organizationId)
    {
        return $this->organizationRepo->find($organizationId);
    }

    public function createOrganization(int $userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            $organization = $this->organizationRepo->create([
                "name" => $data["name"],
                "owner_id" => $userId,
            ]);

            if (!empty($data["logo"])) {
                $path = $this->fileStorage->storeOrganizationLogo(
                    $data["logo"],
                    $organization->id,
                );

                $this->organizationRepo->update($organization, [
                    "logo_url" => $path,
                ]);
            }

            $this->organizationUserRepo->addUser([
                "organization_id" => $organization->id,
                "user_id" => $userId,
                "role" => OrganizationUser::ROLE_OWNER,
                "joined_at" => now(),
            ]);

            return $organization->refresh();
        });
    }

    public function orgDropdownOptions($user, $activeOrgId)
    {
        $organizations = $user->organizations;

        $organizations = $organizations
            ->filter(function ($organization) use ($activeOrgId) {
                return $organization->id != $activeOrgId;
            })
            ->map(function ($organization) {
                return [
                    "id" => $organization->id,
                    "text" => ucwords($organization->name),
                    "full_logo_url" => $organization->full_logo_url,
                ];
            })
            ->values();

        $activeOrgInfo = $user
            ->organizations()
            ->where("organizations.id", $activeOrgId)
            ->first();

        if ($activeOrgInfo) {
            $activeOrgInfo = [
                "id" => $activeOrgInfo->id,
                "text" => ucwords($activeOrgInfo->name),
                "full_logo_url" => $activeOrgInfo->full_logo_url,
            ];
        }

        return [
            "meta" => [
                "active_organization_id" => $activeOrgId,
                "active_organization_info" => $activeOrgInfo,
            ],
            "organizations" => $organizations,
        ];
    }

    public function memberList($user, array $filters, $organizationId)
    {
        $totalMember = $this->organizationUserRepo->countMembers(
            $organizationId,
        );
        $totalAdmins = $this->organizationUserRepo->countMembers(
            $organizationId,
            "admin",
        );
        $pendingInvites = $this->invitationRepo->countPendingInvitations(
            $organizationId,
        );

        $paginatedMembers = $this->organizationUserRepo->getPaginatedMemberList(
            $user,
            $filters,
            $organizationId,
        );

        $data = [
            "stats" => [
                "total_members" => $totalMember,
                "total_admins" => $totalAdmins,
                "pending_invites" => $pendingInvites,
            ],
            "members" => $paginatedMembers,
        ];

        return $data;
    }

    public function deleteMember($user, $organizationId, $userId)
    {
        $organizationUser = $this->organizationUserRepo->getMember(
            $organizationId,
            $userId,
        );

        if (!$organizationUser) {
            throw new Exception("Member not found");
        }

        if ($organizationUser->user_id === $user->id) {
            throw new Exception("You cannot delete yourself");
        }

        if ($organizationUser->role === OrganizationUser::ROLE_OWNER) {
            throw new Exception("Owner cannot be deleted");
        }

        $this->organizationUserRepo->delete($organizationUser);

        return true;
    }

    public function changeRole($user, $organizationId, $userId, $role)
    {
        $organizationUser = $this->organizationUserRepo->getMember(
            $organizationId,
            $userId,
        );

        if (!$organizationUser) {
            throw new Exception("Member not found");
        }

        if ($organizationUser->user_id === $user->id) {
            throw new Exception("You cannot change your role");
        }

        if ($organizationUser->role === OrganizationUser::ROLE_OWNER) {
            throw new Exception("Owner cannot be changed");
        }

        $this->organizationUserRepo->update($organizationUser, [
            "role" => $role,
        ]);

        return true;
    }
}
