<?php

namespace App\Repositories;

use App\Models\OrganizationUser;

class OrganizationUserRepository
{
    public function all()
    {
        return OrganizationUser::all();
    }

    public function find($id)
    {
        return OrganizationUser::find($id);
    }

    public function addUser(array $data)
    {
        return OrganizationUser::create($data);
    }

    public function getPaginatedMemberList($user, array $filters, $organizationId)
    {
        $query = OrganizationUser::query()
            ->select('organization_users.*')
            ->join('users', 'organization_users.user_id', '=', 'users.id')
            ->where('organization_users.organization_id', $organizationId)
            ->with('user:id,name,email,avatar_url');

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('users.name', 'LIKE', $searchTerm)
                    ->orWhere('users.email', 'LIKE', $searchTerm);
            });
        }

        $sortBy = $filters['sort_by'];
        $orderBy = $filters['order_by'];

        switch ($sortBy) {
            case 'name':
            case 'user':
                $query->orderBy('users.name', $orderBy);
                break;
            case 'email':
                $query->orderBy('users.email', $orderBy);
                break;
            case 'role':
                $query->orderBy('organization_users.role', $orderBy);
                break;
            default:
                $query->orderBy('organization_users.joined_at', $orderBy);
                break;
        }

        return $query->paginate($filters['page_size']);
    }

    public function countMembers($organizationId, $role = null)
    {
        $query = OrganizationUser::where('organization_id', $organizationId);

        if ($role) {
            $query->where('role', $role);
        }

        return $query->count();
    }

    public function isMember(string $email, $organizationId): bool
    {
        return OrganizationUser::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->where('organization_id', $organizationId)->exists();
    }

    public function isMemberByUserId($userId, $organizationId)
    {
        return OrganizationUser::where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->exists();
    }
}
