<?php

namespace App\Services;

use App\Repositories\AuditTrailRepository;
use Illuminate\Http\Request;

class AuditTrailService
{
    /**
     * Valid action types for audit trail entries.
     */
    public const ACTION_TYPES = [
        "expense_created",
        "expense_updated",
        "expense_deleted",
        "expense_approved",
        "expense_rejected",
        "budget_created",
        "budget_updated",
        "budget_deleted",
        "member_invited",
        "member_removed",
        "member_role_updated",
        "invitation_accepted",
        "invitation_rejected",
        "category_created",
        "category_updated",
        "category_deleted",
        "organization_updated",
    ];

    public function __construct(
        protected AuditTrailRepository $auditTrailRepository,
    ) {}

    /**
     * Record a new audit trail entry.
     *
     * @param  int         $organizationId
     * @param  int|null    $userId
     * @param  string      $actionType   One of self::ACTION_TYPES
     * @param  string      $description  Human-readable description
     * @param  array|null  $metadata     Additional structured data
     * @param  string|null $ipAddress
     */
    public function log(
        int $organizationId,
        ?int $userId,
        string $actionType,
        string $description,
        ?array $metadata = null,
        ?string $ipAddress = null,
    ): void {
        $this->auditTrailRepository->create([
            "organization_id" => $organizationId,
            "user_id" => $userId,
            "action_type" => $actionType,
            "description" => $description,
            "metadata" => $metadata,
            "ip_address" => $ipAddress,
        ]);
    }

    /**
     * Record an audit trail entry from a Request context.
     * Automatically resolves user ID and IP address from the request.
     */
    public function logFromRequest(
        Request $request,
        int $organizationId,
        string $actionType,
        string $description,
        ?array $metadata = null,
    ): void {
        $this->log(
            organizationId: $organizationId,
            userId: $request->user()?->id,
            actionType: $actionType,
            description: $description,
            metadata: $metadata,
            ipAddress: $request->ip(),
        );
    }

    /**
     * Get paginated audit trails for an organization with optional filters.
     *
     * @param  int   $organizationId
     * @param  array $filters  Keys: user_id, action_type, date_from, date_to, page_size
     */
    public function getAuditTrails(int $organizationId, array $filters): array
    {
        $paginator = $this->auditTrailRepository->paginate(
            $organizationId,
            $filters,
        );

        $items = collect($paginator->items())->map(function ($trail) {
            $user = $trail->user;

            return [
                "id" => $trail->id,
                "user" => $user
                    ? [
                        "id" => $user->id,
                        "name" => $user->name,
                        "email" => $user->email,
                        "full_avatar_url" => $user->full_avatar_url,
                    ]
                    : null,
                "action_type" => $trail->action_type,
                "description" => $trail->description,
                "metadata" => $trail->metadata,
                "ip_address" => $trail->ip_address,
                "created_at" => $trail->created_at,
            ];
        });

        return [
            "data" => $items,
            "current_page" => $paginator->currentPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "last_page" => $paginator->lastPage(),
        ];
    }

    /**
     * Return all available action types (for filtering dropdowns etc.)
     */
    public function getActionTypes(): array
    {
        return self::ACTION_TYPES;
    }
}
