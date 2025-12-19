<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Seed comprehensive permissions
        $permissions = [
            // Users
            ['name' => 'users.view', 'description' => 'View users list'],
            ['name' => 'users.create', 'description' => 'Create new users'],
            ['name' => 'users.edit', 'description' => 'Edit users'],
            ['name' => 'users.delete', 'description' => 'Delete users'],
            
            // Roles
            ['name' => 'roles.view', 'description' => 'View roles'],
            ['name' => 'roles.create', 'description' => 'Create roles'],
            ['name' => 'roles.edit', 'description' => 'Edit roles'],
            ['name' => 'roles.delete', 'description' => 'Delete roles'],
            
            // Permissions
            ['name' => 'permissions.view', 'description' => 'View permissions'],
            ['name' => 'permissions.manage', 'description' => 'Manage permissions'],
            
            // Clients
            ['name' => 'clients.view', 'description' => 'View clients'],
            ['name' => 'clients.create', 'description' => 'Create clients'],
            ['name' => 'clients.edit', 'description' => 'Edit clients'],
            ['name' => 'clients.delete', 'description' => 'Delete clients'],
            
            // Chats
            ['name' => 'chats.view', 'description' => 'View chats'],
            ['name' => 'chats.respond', 'description' => 'Respond to chats'],
            ['name' => 'chats.transfer', 'description' => 'Transfer chats'],
            ['name' => 'chats.close', 'description' => 'Close chats'],
            
            // Visitors
            ['name' => 'visitors.view', 'description' => 'View visitors'],
            ['name' => 'visitors.initiate', 'description' => 'Initiate chat with visitors'],
            
            // Reports
            ['name' => 'reports.view', 'description' => 'View reports'],
            ['name' => 'reports.export', 'description' => 'Export reports'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                array_merge($perm, ['slug' => $perm['name'], 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Assign permissions to roles
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $managerRole = DB::table('roles')->where('name', 'manager')->first();
        $agentRole = DB::table('roles')->where('name', 'agent')->first();

        if ($adminRole) {
            // Admin gets all permissions
            $allPermIds = DB::table('permissions')->pluck('id');
            foreach ($allPermIds as $permId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permId, 'role_id' => $adminRole->id]
                );
            }
        }

        if ($managerRole) {
            // Manager gets team and reports access
            $managerPerms = ['chats.view', 'chats.respond', 'chats.transfer', 'chats.close', 'visitors.view', 'visitors.initiate', 'reports.view'];
            $permIds = DB::table('permissions')->whereIn('name', $managerPerms)->pluck('id');
            foreach ($permIds as $permId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permId, 'role_id' => $managerRole->id]
                );
            }
        }

        if ($agentRole) {
            // Agent gets chat access only
            $agentPerms = ['chats.view', 'chats.respond', 'visitors.view', 'visitors.initiate'];
            $permIds = DB::table('permissions')->whereIn('name', $agentPerms)->pluck('id');
            foreach ($permIds as $permId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permId, 'role_id' => $agentRole->id]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('permission_role')->truncate();
        DB::table('permissions')->truncate();
    }
};
