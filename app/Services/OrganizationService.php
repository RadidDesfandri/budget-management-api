<?php

namespace App\Services;

use App\Repositories\OrganizationRepository;
use App\Repositories\OrganizationUserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $organization->refresh();
        });
    }
}
