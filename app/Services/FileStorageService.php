<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FileStorageService
{
    public function storeOrganizationLogo(UploadedFile $file, int $organizationId): string
    {
        return $file->store(
            "organizations/{$organizationId}/logo",
            'public'
        );
    }
}
