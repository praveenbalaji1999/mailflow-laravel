<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Group;
use App\Models\SmtpSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        Admin::firstOrCreate(
            ['email' => 'admin@mailflow.local'],
            ['name' => 'Admin User', 'password' => Hash::make('Admin@123')]
        );

        // Groups
        $groups = [
            ['name' => 'All Customers',      'description' => 'All customer contacts'],
            ['name' => 'Employees',           'description' => 'Internal employees'],
            ['name' => 'HR Team',             'description' => 'Human resources team'],
            ['name' => 'Sales Team',          'description' => 'Sales and account managers'],
            ['name' => 'Premium Customers',   'description' => 'Premium tier customers'],
            ['name' => 'Marketing',           'description' => 'Marketing distribution list'],
        ];

        foreach ($groups as $group) {
            Group::firstOrCreate(['name' => $group['name']], $group);
        }

        // SMTP placeholder
        SmtpSetting::firstOrCreate(
            ['id' => 1],
            [
                'host'       => 'smtp.gmail.com',
                'port'       => 587,
                'encryption' => 'tls',
                'username'   => 'admin@example.com',
                'password'   => '',
                'from_name'  => 'MailFlow',
                'from_email' => 'admin@example.com',
            ]
        );
    }
}
