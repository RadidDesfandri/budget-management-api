<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $orgId = $request->input('organization_id') ?? $request->route('organization_id');

        if (!$orgId) {
            return $next($request);
        }

        $hasAccess = $request->user()
            ->organizations()
            ->where('organizations.id', $orgId)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not belong to this organization.',
                'data'    => null,
                'error'   => 'UNAUTHORIZED',
                'statusCode' => 403,
            ], 403);
        }

        return $next($request);
    }
}
