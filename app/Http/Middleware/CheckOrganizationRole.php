<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string ...$roles  Role yang diperbolehkan (pisahkan dengan koma)
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
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

        $user = Auth::user();

        $hasPermission = $user
            ->organizations()
            ->where("organizations.id", $orgId)
            ->wherePivotIn("role", $roles)
            ->exists();

        if (!$hasPermission) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "You do not have permission to access this organization.",
                    "data" => null,
                    "error" => "UNAUTHORIZED",
                    "statusCode" => 403,
                ],
                403,
            );
        }

        return $next($request);
    }
}
