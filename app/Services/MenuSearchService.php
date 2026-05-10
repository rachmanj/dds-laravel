<?php

namespace App\Services;

use App\Models\User;

class MenuSearchService
{
    /**
     * @return list<array{
     *     title: string,
     *     route: string,
     *     icon: string,
     *     category: string,
     *     breadcrumb: string,
     *     keywords: list<string>,
     *     searchText: string
     * }>
     */
    public function buildForUser(User $user): array
    {
        $items = [];

        $this->addDashboardAndCommon($items);
        $this->addDomainAssistant($user, $items);
        $this->addAdditionalDocuments($user, $items);
        $this->addInvoices($user, $items);
        $this->addSolarPriceHistories($items);
        $this->addDistributions($user, $items);
        $this->addReports($user, $items);
        $this->addMessages($items);
        $this->addMasterData($user, $items);
        $this->addAdmin($user, $items);

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addDashboardAndCommon(array &$items): void
    {
        $this->push(
            $items,
            'Dashboard 1',
            'dashboard',
            'fas fa-tachometer-alt',
            'Dashboard',
            'MAIN > Dashboard > Dashboard 1',
            ['home', 'overview']
        );

        $this->push(
            $items,
            'Dashboard 2',
            'processing-analytics.index',
            'fas fa-tachometer-alt',
            'Dashboard',
            'MAIN > Dashboard > Dashboard 2',
            ['processing', 'analytics']
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addDomainAssistant(User $user, array &$items): void
    {
        if (! config('services.domain_assistant.enabled')) {
            return;
        }

        if (! $user->can('access-domain-assistant')) {
            return;
        }

        $this->push(
            $items,
            'Domain Assistant',
            'assistant.index',
            'fas fa-robot',
            'MAIN',
            'MAIN > Domain Assistant',
            ['assistant', 'chat', 'ai']
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addAdditionalDocuments(User $user, array &$items): void
    {
        if (! $user->can('view-additional-documents')) {
            return;
        }

        $this->push(
            $items,
            'Additional Documents Dashboard',
            'additional-documents.dashboard',
            'fas fa-file-alt',
            'Additional Documents',
            'MAIN > Additional Documents > Dashboard',
            ['documents', 'dashboard']
        );

        $this->push(
            $items,
            'List Additional Documents',
            'additional-documents.index',
            'fas fa-file-alt',
            'Additional Documents',
            'MAIN > Additional Documents > List Documents',
            ['documents', 'list']
        );

        $this->push(
            $items,
            'Create Additional Document',
            'additional-documents.create',
            'fas fa-file-alt',
            'Additional Documents',
            'MAIN > Additional Documents > Create New Document',
            ['documents', 'create', 'new']
        );

        if ($user->can('import-additional-documents')) {
            $this->push(
                $items,
                'Import Additional Documents',
                'additional-documents.import',
                'fas fa-file-alt',
                'Additional Documents',
                'MAIN > Additional Documents > Import Documents',
                ['import', 'upload', 'csv']
            );
        } elseif ($user->can('import-general-documents')) {
            $this->push(
                $items,
                'Import Additional Documents',
                'additional-documents.import-general',
                'fas fa-file-alt',
                'Additional Documents',
                'MAIN > Additional Documents > Import Documents',
                ['import', 'upload', 'general']
            );
        }

        if ($user->can('sync-sap-ito')) {
            $this->push(
                $items,
                'SAP ITO Sync',
                'admin.sap-sync-ito',
                'fas fa-file-alt',
                'Additional Documents',
                'MAIN > Additional Documents > SAP ITO Sync',
                ['sap', 'ito', 'sync']
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addInvoices(User $user, array &$items): void
    {
        if (! $user->can('view-invoices')) {
            return;
        }

        $this->push(
            $items,
            'Invoices Dashboard',
            'invoices.dashboard',
            'fas fa-file-invoice',
            'Invoices',
            'MAIN > Invoices > Dashboard',
            ['invoice', 'dashboard']
        );

        $this->push(
            $items,
            'List Invoices',
            'invoices.index',
            'fas fa-file-invoice',
            'Invoices',
            'MAIN > Invoices > List Invoices',
            ['invoice', 'list']
        );

        $this->push(
            $items,
            'Create New Invoice',
            'invoices.create',
            'fas fa-file-invoice',
            'Invoices',
            'MAIN > Invoices > Create New Invoice',
            ['invoice', 'create', 'new']
        );

        $this->push(
            $items,
            'Invoice Attachments',
            'invoices.attachments.index',
            'fas fa-file-invoice',
            'Invoices',
            'MAIN > Invoices > Invoice Attachments',
            ['invoice', 'attachments', 'files']
        );

        if ($user->can('view-invoice-payment')) {
            $this->push(
                $items,
                'Invoice Payments',
                'invoices.payments.dashboard',
                'fas fa-file-invoice',
                'Invoices',
                'MAIN > Invoices > Invoice Payments',
                ['invoice', 'payments', 'payment']
            );
        }

        if ($user->can('view-sap-update')) {
            $this->push(
                $items,
                'SAP Update',
                'invoices.sap-update.index',
                'fas fa-file-invoice',
                'Invoices',
                'MAIN > Invoices > SAP Update',
                ['sap', 'update']
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addSolarPriceHistories(array &$items): void
    {
        $this->push(
            $items,
            'Solar Price Histories',
            'solar-price-histories.index',
            'fas fa-sun',
            'MAIN',
            'MAIN > Solar Price Histories',
            ['solar', 'price', 'pertamina']
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addDistributions(User $user, array &$items): void
    {
        if (! $user->can('view-distributions')) {
            return;
        }

        $this->push(
            $items,
            'Distribution Dashboard',
            'distributions.dashboard',
            'fas fa-share-alt',
            'Distribution',
            'MAIN > Distribution > Dashboard',
            ['distribution', 'dashboard']
        );

        $this->push(
            $items,
            'Distribution List',
            'distributions.index',
            'fas fa-share-alt',
            'Distribution',
            'MAIN > Distribution > Distribution List',
            ['distribution', 'list']
        );

        if ($user->can('create-distributions')) {
            $this->push(
                $items,
                'Create Distribution',
                'distributions.create',
                'fas fa-share-alt',
                'Distribution',
                'MAIN > Distribution > Create Distribution',
                ['distribution', 'create', 'new']
            );
        }

        $this->push(
            $items,
            'Numbering Stats',
            'distributions.numbering-stats',
            'fas fa-share-alt',
            'Distribution',
            'MAIN > Distribution > Numbering Stats',
            ['distribution', 'numbering', 'stats']
        );

        if ($user->department) {
            $this->push(
                $items,
                'Department History',
                'distributions.department-history',
                'fas fa-share-alt',
                'Distribution',
                'MAIN > Distribution > Department History',
                ['distribution', 'department', 'history']
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addReports(User $user, array &$items): void
    {
        if (! $user->can('view-reconcile')) {
            return;
        }

        $this->push(
            $items,
            'Reconciliation',
            'reconcile.index',
            'fas fa-chart-line',
            'Reports',
            'MAIN > Reports > Reconciliation',
            ['reconcile', 'report', 'reports']
        );

        if ($user->hasAnyRole(['superadmin', 'admin', 'accounting'])) {
            $this->push(
                $items,
                'Accounting Monthly Invoice Fulfillment',
                'accounting-fulfillment.index',
                'fas fa-chart-line',
                'Reports',
                'MAIN > Reports > Accounting Monthly Invoice Fulfillment',
                ['accounting', 'fulfillment', 'monthly', 'invoice']
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addMessages(array &$items): void
    {
        $this->push(
            $items,
            'Inbox',
            'messages.index',
            'fas fa-envelope',
            'Messages',
            'MAIN > Messages > Inbox',
            ['messages', 'mail', 'inbox']
        );

        $this->push(
            $items,
            'Sent Messages',
            'messages.sent',
            'fas fa-envelope',
            'Messages',
            'MAIN > Messages > Sent',
            ['messages', 'sent', 'mail']
        );

        $this->push(
            $items,
            'Compose Message',
            'messages.create',
            'fas fa-envelope',
            'Messages',
            'MAIN > Messages > Compose',
            ['messages', 'compose', 'new', 'mail']
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addMasterData(User $user, array &$items): void
    {
        if (! $user->can('view-master')) {
            return;
        }

        $this->push(
            $items,
            'Projects',
            'admin.projects.index',
            'fas fa-database',
            'Master Data',
            'MAIN > Master Data > Projects',
            ['master', 'projects']
        );

        $this->push(
            $items,
            'Departments',
            'admin.departments.index',
            'fas fa-database',
            'Master Data',
            'MAIN > Master Data > Departments',
            ['master', 'departments']
        );

        $this->push(
            $items,
            'Document Types',
            'admin.additional-document-types.index',
            'fas fa-database',
            'Master Data',
            'MAIN > Master Data > Document Types',
            ['master', 'document', 'types']
        );

        $this->push(
            $items,
            'Invoice Types',
            'admin.invoice-types.index',
            'fas fa-database',
            'Master Data',
            'MAIN > Master Data > Invoice Types',
            ['master', 'invoice', 'types']
        );

        $this->push(
            $items,
            'Suppliers',
            'admin.suppliers.index',
            'fas fa-database',
            'Master Data',
            'MAIN > Master Data > Suppliers',
            ['master', 'suppliers', 'vendor']
        );

        if ($user->can('reset-document-status')) {
            $this->push(
                $items,
                'Document Status',
                'admin.document-status.index',
                'fas fa-database',
                'Master Data',
                'MAIN > Master Data > Document Status',
                ['master', 'document', 'status', 'reset']
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addAdmin(User $user, array &$items): void
    {
        if (! $user->can('view-admin')) {
            return;
        }

        $this->push(
            $items,
            'Assistant Request Log',
            'admin.assistant-report.index',
            'fas fa-robot',
            'ADMIN',
            'ADMIN > Assistant request log',
            ['admin', 'assistant', 'log', 'report']
        );

        $this->push(
            $items,
            'Users',
            'admin.users.index',
            'fas fa-users',
            'ADMIN',
            'ADMIN > Users',
            ['admin', 'users']
        );

        $this->push(
            $items,
            'Roles',
            'admin.roles.index',
            'fas fa-user-shield',
            'ADMIN',
            'ADMIN > Roles & Permissions > Roles',
            ['admin', 'roles', 'permissions']
        );

        $this->push(
            $items,
            'Permissions',
            'admin.permissions.index',
            'fas fa-user-shield',
            'ADMIN',
            'ADMIN > Roles & Permissions > Permissions',
            ['admin', 'permissions', 'roles']
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function push(
        array &$items,
        string $title,
        string $routeName,
        string $icon,
        string $category,
        string $breadcrumb,
        array $keywords = [],
    ): void {
        $items[] = [
            'title' => $title,
            'route' => route($routeName),
            'icon' => $icon,
            'category' => $category,
            'breadcrumb' => $breadcrumb,
            'keywords' => array_values($keywords),
            'searchText' => $this->buildSearchText($title, $breadcrumb, $keywords),
        ];
    }

    /**
     * @param  list<string>  $keywords
     */
    private function buildSearchText(string $title, string $breadcrumb, array $keywords): string
    {
        $parts = array_merge(
            [mb_strtolower($title), mb_strtolower($breadcrumb)],
            array_map(fn (string $k) => mb_strtolower($k), $keywords)
        );

        return implode(' ', $parts);
    }
}
