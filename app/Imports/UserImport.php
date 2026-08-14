<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;

class UserImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $user = User::firstOrCreate(
            ['email' => $row['email'] ?? ''],
            [
                'name' => $row['name'] ?? '',
                'password' => Hash::make('password'),
            ]
        );

        if (!empty($row['roles'])) {
            $roleNames = array_map('trim', explode(',', $row['roles']));
            $roles = Role::whereIn('name', $roleNames)->pluck('name')->toArray();
            $user->syncRoles($roles);
        }

        return $user;
    }
}
