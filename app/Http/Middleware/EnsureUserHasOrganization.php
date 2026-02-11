<?php

namespace App\Http\Middleware;

use App\Helpers\OrganizationHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

        if ($user->organizations()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'User has no organization',
                'data'    => null,
                'error'   => 'NO_ORGANIZATION',
                'statusCode' => 403,
            ], 403);
        }

        if (!session()->has(OrganizationHelper::ORGANIZATION_ID)) {
            OrganizationHelper::setOrganizationId($user->organizations()->first()->id);
        }

        return $next($request);
    }
}
