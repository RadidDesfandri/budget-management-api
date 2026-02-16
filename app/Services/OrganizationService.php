<?php

namespace App\Services;

use App\Helpers\OrganizationHelper;
use App\Models\OrganizationUser;
use App\Repositories\OrganizationRepository;
use App\Repositories\OrganizationUserRepository;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    public function __construct(
        protected OrganizationRepository $organizationRepo,
        protected OrganizationUserRepository $organizationUserRepo,
        protected FileStorageService $fileStorage

    ) {}

    public function createOrganization(int $userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            $organization = $this->organizationRepo->create([
                'name' => $data['name'],
                'owner_id' => $userId,
            ]);

            if (!empty($data['logo'])) {
                $path = $this->fileStorage
                    ->storeOrganizationLogo($data['logo'], $organization->id);

                $this->organizationRepo->update(
                    $organization,
                    ['logo_url' => $path]
                );
            }

            $this->organizationUserRepo->addUser([
                'organization_id' => $organization->id,
                'user_id' => $userId,
                'role' => OrganizationUser::ROLE_OWNER,
                'joined_at' => now(),
            ]);

            return $organization->refresh();
        });
    }

    public function orgDropdownOptions($user)
    {
        $organizations = $user->organizations;
        $activeOrgId = OrganizationHelper::getOrganizationId();

        $organizations = $organizations->filter(function ($organization) use ($activeOrgId) {
            return $organization->id != $activeOrgId;
        })
            ->map(function ($organization) {
                return [
                    'id' => $organization->id,
                    'text' => ucwords($organization->name),
                    'full_logo_url' => $organization->full_logo_url
                ];
            })->values();

        $activeOrgInfo = $user->organizations()->where('organizations.id', $activeOrgId)->first();

        if ($activeOrgInfo) {
            $activeOrgInfo = [
                'id' => $activeOrgInfo->id,
                'text' => ucwords($activeOrgInfo->name),
                'full_logo_url' => $activeOrgInfo->full_logo_url
            ];
        }

        return [
            'meta' => [
                'active_organization_id' => $activeOrgId,
                'active_organization_info' => $activeOrgInfo,
            ],
            'organizations' => $organizations
        ];
    }

    public function memberList($user, array $filters)
    {
        $totalMember = $this->organizationUserRepo->countMembers($user->current_organization_id);
        $totalAdmins = $this->organizationUserRepo->countMembers($user->current_organization_id, 'admin');

        $paginatedMembers = $this->organizationUserRepo->getPaginatedMemberList($user, $filters);

        $data = [
            'stats' => [
                'total_members'   => $totalMember,
                'total_admins'    => $totalAdmins,
                'pending_invites' => 0, // change real data
            ],
            'members' => $paginatedMembers,
        ];

        return $data;
    }
}
