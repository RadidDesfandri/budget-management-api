<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    public function storeOrganizationLogo(
        UploadedFile $file,
        int $organizationId,
    ): string {
        return $file->store("organizations/{$organizationId}/logo", "public");
    }

    public function storeExpenseReceipt(
        UploadedFile $file,
        int $organizationId,
        int $expenseId,
    ): string {
        return $file->store(
            "organizations/{$organizationId}/expenses/{$expenseId}/receipts",
            "public",
        );
    }

    public function deleteFile(string $path): bool
    {
        return Storage::disk("public")->delete($path);
    }
}
