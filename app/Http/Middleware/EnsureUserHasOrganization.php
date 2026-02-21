<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $orgId = $request->route("organization_id");

        if (!$orgId) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Organization ID is missing in URL.",
                    "data" => null,
                    "error" => "MISSING_ORG_ID",
                    "statusCode" => 400,
                ],
                400,
            );
        }

        $organization = Organization::find($orgId);

        if (!$organization) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Organization not found.",
                    "data" => null,
                    "error" => "NOT_FOUND",
                    "statusCode" => 404,
                ],
                404,
            );
        }

        $hasAccess = $request
            ->user()
            ->organizations()
            ->where("organizations.id", $orgId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized: Access to Organization",
                    "data" => null,
                    "error" => "UNAUTHORIZED",
                    "statusCode" => 403,
                ],
                403,
            );
        }

        app()->instance("active_organization_id", $orgId);

        return $next($request);
    }
}
