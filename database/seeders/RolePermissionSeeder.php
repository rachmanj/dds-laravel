<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Role management
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',

            // Permission management
            'view-permissions',
            'assign-permissions',

            // Additional Documents
            'view-additional-documents',
            'create-additional-documents',
            'edit-additional-documents',
            'delete-additional-documents',
            'on-the-fly-addoc-feature',

            // Invoices
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices',
            'view-invoice-payment',
            'update-invoice-payment',

            // Invoice Attachments
            'inv-attachment-view',
            'inv-attachment-create',
            'inv-attachment-edit',
            'inv-attachment-delete',

            // Distributions
            'view-distributions',
            'create-distributions',
            'edit-distributions',
            'delete-distributions',
            'view-distributions-numbering-stats',
            'view-document-distribution-history',

            // Suppliers
            'view-suppliers',
            'create-suppliers',
            'edit-suppliers',
            'delete-suppliers',

            //Master
            'view-admin',
            'view-master',

            // Reports
            'view-reports',

            // Document Status Management
            'reset-document-status',

            // See All Records Switch
            'see-all-record-switch',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view-users',
            'create-users',
            'edit-users',
            'view-roles',
            'view-permissions',
            'assign-permissions',
            'view-additional-documents',
            'create-additional-documents',
            'edit-additional-documents',
            'delete-additional-documents',
            'on-the-fly-addoc-feature',
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices',
            'view-invoice-payment',
            'update-invoice-payment',
            'inv-attachment-view',
            'inv-attachment-create',
            'inv-attachment-edit',
            'inv-attachment-delete',
            'view-suppliers',
            'create-suppliers',
            'edit-suppliers',
            'delete-suppliers',
            'view-distributions',
            'create-distributions',
            'edit-distributions',
            'delete-distributions',
            'view-distributions-numbering-stats',
            'view-document-distribution-history',
            'reset-document-status',
            'see-all-record-switch',
            'view-master',
            'view-admin',
        ]);

        $logistic = Role::firstOrCreate(['name' => 'logistic']);
        $logistic->givePermissionTo([
            'view-additional-documents',
            'create-additional-documents',
            'edit-additional-documents',
            'delete-additional-documents',
            'view-distributions',
            'create-distributions',
            'edit-distributions',
            'delete-distributions',
        ]);

        $accounting = Role::firstOrCreate(['name' => 'accounting']);
        $accounting->givePermissionTo([
            'view-additional-documents',
            'create-additional-documents',
            'edit-additional-documents',
            'delete-additional-documents',
            'on-the-fly-addoc-feature',
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices',
            'view-invoice-payment',
            'update-invoice-payment',
            'inv-attachment-view',
            'inv-attachment-create',
            'inv-attachment-edit',
            'inv-attachment-delete',
            'view-suppliers',
            'create-suppliers',
            'edit-suppliers',
            'delete-suppliers',
            'view-distributions',
            'create-distributions',
            'edit-distributions',
            'delete-distributions',
            'view-master',
            'see-all-record-switch',
        ]);

        $finance = Role::firstOrCreate(['name' => 'finance']);
        $finance->givePermissionTo([
            'view-additional-documents',
            'create-additional-documents',
            'edit-additional-documents',
            'delete-additional-documents',
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices',
            'view-invoice-payment',
            'update-invoice-payment',
            'inv-attachment-view',
            'inv-attachment-create',
            'inv-attachment-edit',
            'inv-attachment-delete',
            'view-suppliers',
            'create-suppliers',
            'edit-suppliers',
            'delete-suppliers',
            'view-distributions',
            'create-distributions',
            'edit-distributions',
            'delete-distributions',
            'view-master',
            'view-admin',
            'see-all-record-switch',
        ]);

        $cashierho = Role::firstOrCreate(['name' => 'cashierho']);
        $cashierho->givePermissionTo([
            'view-invoices',
            'view-invoice-payment',
            'update-invoice-payment',
            'inv-attachment-view',
            'inv-attachment-create',
            'inv-attachment-edit',
            'inv-attachment-delete',
            'view-suppliers',
            'view-distributions',
            'create-distributions',
            'edit-distributions',
            'see-all-record-switch',
        ]);
    }
}
