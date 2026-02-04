<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function all()
    {
        return User::all();
    }

    public function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function update(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function delete(User $user)
    {
        $user->delete();
    }

    public function find($id)
    {
        return User::find($id);
    }

    public function findOrFail($id)
    {
        return User::findOrFail($id);
    }
}
