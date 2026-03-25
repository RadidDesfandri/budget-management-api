<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrailService,
    ) {}

    /**
     * List audit trails for the organization with optional filters.
     *
     * Query params:
     *   - user_id      (integer, optional)
     *   - action_type  (string, optional) — one of the defined action types
     *   - date_from    (date Y-m-d, optional)
     *   - date_to      (date Y-m-d, optional)
     *   - page_size    (integer 1–100, optional, default 10)
     *   - search       (string, optional)
     */
    public function index(Request $request, $organization_id)
    {
        $filters = $request->validate([
            "user_id" => "nullable|integer|exists:users,id",
            "action_type" => [
                "nullable",
                "string",
                "in:" . implode(",", AuditTrailService::ACTION_TYPES),
            ],
            "date_from" => "nullable|date_format:Y-m-d",
            "date_to" => "nullable|date_format:Y-m-d|after_or_equal:date_from",
            "page_size" => "nullable|integer|min:1|max:100",
            "search" => "nullable|string",
        ]);

        $auditTrails = $this->auditTrailService->getAuditTrails(
            (int) $organization_id,
            $filters,
        );

        return $this->successResponse(
            "Audit trails retrieved successfully",
            $auditTrails,
        );
    }

    /**
     * Return the list of available action types for filter dropdowns.
     */
    public function actionTypes()
    {
        return $this->successResponse(
            "Action types retrieved successfully",
            $this->auditTrailService->getActionTypes(),
        );
    }
}
