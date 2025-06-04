<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // -----------------------------
        // 1. Create Roles
        // -----------------------------
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $student = Role::firstOrCreate(['name' => 'student']);
        $companyRep = Role::firstOrCreate(['name' => 'company_representative']);

        // -----------------------------
        // 2. Define All Permissions
        // -----------------------------
        $permissions = [

            // --- EVENT MANAGEMENT ---
            'view events',
            'create events',
            'update events',
            'archive events',
            'create jobfair',
            'set branding schedule',
            'add sessions',
            'update sessions',
            'view event agenda',

            // --- REGISTRATION & ATTENDANCE ---
            'register for events',
            'cancel registration',
            'mark attendance',
            'view attendance list',
            'export attendance',

            // --- JOB FAIR COMPANY PARTICIPATION ---
            'submit jobfair form',
            'edit jobfair schedule',

            // --- JOB FAIR INTERVIEW MANAGEMENT ---
            'view approved companies',
            'request interview',
            'approve interview',
            'reject interview',
            'view interview queues',
            'update interview queue',
            'manage interview queue',
            'mark interview result',

            // --- DASHBOARDS ---
            'view admin dashboard',
            'view staff dashboard',
            'view student dashboard',
            'view company dashboard',

            // --- NOTIFICATIONS & COMMUNICATION ---
            'view notifications',
            'send notifications',
            'receive interview status email',

            // --- FEEDBACK & AI INSIGHTS ---
            'create feedback form',
            'submit feedback',
            'view feedback insight',
            'generate ai feedback summary',

            // --- SLIDO Q&A ---
            'set slido link',
            'view slido link',
            'moderate slido questions'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // -----------------------------
        // 3. Assign Permissions to Roles
        // -----------------------------

        // === ADMIN ===
        $admin->givePermissionTo([
            'view events',
            'create events',
            'update events',
            'archive events',
            'create jobfair',
            'set branding schedule',
            'add sessions',
            'update sessions',
            'view event agenda',
            'view admin dashboard',
            'mark attendance',
            'export attendance',
            'manage interview queue',
            'send notifications',
            'create feedback form',
            'view feedback insight',
            'generate ai feedback summary',
            'set slido link',
            'moderate slido questions',
        ]);

        // === STAFF ===
        $staff->givePermissionTo([
            'view events',
            'view event agenda',
            'mark attendance',
            'view attendance list',
            'view staff dashboard',
            'update interview queue',
            'view interview queues',
            'view notifications',
        ]);

        // === STUDENT ===
        $student->givePermissionTo([
            'view events',
            'view event agenda',
            'register for events',
            'cancel registration',
            'request interview',
            'view approved companies',
            'view student dashboard',
            'submit feedback',
            'view feedback insight',
            'view slido link',
            'view notifications',
            'receive interview status email',
        ]);

        // === COMPANY REPRESENTATIVE ===
        $companyRep->givePermissionTo([
            'view events',
            'submit jobfair form',
            'edit jobfair schedule',
            'approve interview',
            'reject interview',
            'mark interview result',
            'view interview queues',
            'view company dashboard',
            'view slido link',
            'view notifications',
            'receive interview status email',
        ]);
    }
}
