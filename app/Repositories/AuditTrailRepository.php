<?php

namespace App\Repositories;

use App\Models\AuditTrail;

class AuditTrailRepository
{
    public function create(array $data): AuditTrail
    {
        return AuditTrail::create($data);
    }

    public function paginate(int $organizationId, array $filters)
    {
        $perPage = isset($filters["page_size"])
            ? (int) $filters["page_size"]
            : 10;

        $query = AuditTrail::with([
            "user" => function ($q) {
                $q->select("id", "name", "email", "avatar_url");
            },
        ])
            ->where("organization_id", $organizationId)
            ->orderBy("created_at", "desc");

        if (!empty($filters["search"])) {
            $query->where(
                "description",
                "like",
                "%" . $filters["search"] . "%",
            );
        }

        if (!empty($filters["user_id"])) {
            $query->where("user_id", $filters["user_id"]);
        }
        if (!empty($filters["action_type"])) {
            $query->where("action_type", $filters["action_type"]);
        }

        if (!empty($filters["date_from"])) {
            $query->whereDate("created_at", ">=", $filters["date_from"]);
        }

        if (!empty($filters["date_to"])) {
            $query->whereDate("created_at", "<=", $filters["date_to"]);
        }

        return $query->paginate($perPage);
    }
}
