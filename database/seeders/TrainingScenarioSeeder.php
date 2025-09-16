<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdditionalDocument;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\Distribution;
use App\Models\User;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrainingScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users for creating documents
        $logisticsManager = User::where('username', 'logistics_manager')->first() ?? User::where('email', 'log@ninja.com')->first();
        $warehouseStaff1 = User::where('username', 'warehouse_staff1')->first();
        $warehouseStaff2 = User::where('username', 'warehouse_staff2')->first();
        $accountingManager = User::where('username', 'accounting_manager')->first() ?? User::where('email', 'acc@ninja.com')->first();
        $financeManager = User::where('username', 'finance_manager')->first() ?? User::where('email', 'fin@ninja.com')->first();

        if (!$logisticsManager || !$accountingManager || !$financeManager) {
            $this->command->error('Training users not found. Please run TrainingUserSeeder first.');
            return;
        }

        // Get departments for scenarios
        $logisticDept = Department::where('akronim', 'LOG')->first();
        $accountingDept = Department::where('akronim', 'ACC')->first();
        $financeDept = Department::where('akronim', 'FIN')->first();

        if (!$logisticDept || !$accountingDept || !$financeDept) {
            $this->command->error('Required departments not found.');
            return;
        }

        // Check if we've already run this seeder
        if (Invoice::where('invoice_number', 'INV/2025/09/001')->exists()) {
            $this->command->info('Training scenarios already exist. Creating new scenarios...');

            // Create the new scenarios
            $this->createReceiveFlowScenario($logisticsManager, $logisticDept, $accountingDept);
            $this->createAccountingToFinanceScenario($accountingManager, $financeManager, $accountingDept, $financeDept);
            return;
        }

        // Get departments
        $logisticDept = Department::where('akronim', 'LOG')->first();
        $accountingDept = Department::where('akronim', 'ACC')->first();
        $financeDept = Department::where('akronim', 'FIN')->first();
        $wh017 = Department::where('akronim', 'WH017')->first();
        $wh021 = Department::where('akronim', 'WH021')->first();
        $procurementDept = Department::where('akronim', 'PROC')->first();

        // Create some additional documents for training scenarios

        // Scenario 1: Complete workflow - ITO document
        $ito1 = AdditionalDocument::create([
            'type_id' => 1, // ITO
            'document_number' => 'ITO/2025/09/001',
            'document_date' => Carbon::now()->subDays(5),
            'po_no' => 'PO/2025/09/123',
            'project' => '000H',
            'receive_date' => Carbon::now()->subDays(5),
            'created_by' => $logisticsManager->id,
            'remarks' => 'Training scenario - Complete workflow',
            'status' => 'open',
            'cur_loc' => $logisticDept->location_code,
            'ito_creator' => $logisticsManager->name,
            'origin_wh' => 'WH017',
            'destination_wh' => 'WH021',
        ]);

        // Scenario 2: Partial workflow - BAPP document
        $bapp1 = AdditionalDocument::create([
            'type_id' => 3, // BAPP
            'document_number' => 'BAPP/2025/09/002',
            'document_date' => Carbon::now()->subDays(3),
            'po_no' => 'PO/2025/09/124',
            'project' => '017C',
            'receive_date' => Carbon::now()->subDays(3),
            'created_by' => $warehouseStaff1->id,
            'remarks' => 'Training scenario - Partial workflow',
            'status' => 'open',
            'cur_loc' => $wh017->location_code,
        ]);

        // Scenario 3: Document with issues - Goods Issue
        $goodsIssue1 = AdditionalDocument::create([
            'type_id' => 2, // Goods Issue
            'document_number' => 'GI/2025/09/003',
            'document_date' => Carbon::now()->subDays(7),
            'po_no' => 'PO/2025/09/125',
            'project' => '021C',
            'receive_date' => Carbon::now()->subDays(7),
            'created_by' => $warehouseStaff2->id,
            'remarks' => 'Training scenario - Document with issues',
            'status' => 'open',
            'cur_loc' => $wh021->location_code,
        ]);

        // Get suppliers
        $supplier1 = Supplier::where('sap_code', 'V001')->first();
        $supplier2 = Supplier::where('sap_code', 'V002')->first();

        // Create invoices for training scenarios

        // Scenario 4: Invoice ready for payment
        $invoice1 = Invoice::create([
            'invoice_number' => 'INV/2025/09/001',
            'faktur_no' => 'FK-001-2025',
            'invoice_date' => Carbon::now()->subDays(10),
            'receive_date' => Carbon::now()->subDays(9),
            'supplier_id' => $supplier1->id,
            'po_no' => 'PO/2025/09/126',
            'receive_project' => '000H',
            'invoice_project' => '000H',
            'payment_project' => '001H',
            'currency' => 'IDR',
            'amount' => 5000000,
            'type_id' => 1, // Item
            'remarks' => 'Training scenario - Invoice ready for payment',
            'cur_loc' => $accountingDept->location_code,
            'status' => 'open',
            'created_by' => $accountingManager->id,
        ]);

        // Create additional documents for invoice1 with same PO number
        $deliveryOrder1 = AdditionalDocument::create([
            'type_id' => 9, // Delivery Order
            'document_number' => 'DO/2025/09/001',
            'document_date' => Carbon::now()->subDays(11),
            'po_no' => 'PO/2025/09/126', // Same PO as invoice1
            'project' => '000H',
            'receive_date' => Carbon::now()->subDays(10),
            'created_by' => $logisticsManager->id,
            'remarks' => 'Delivery of office supplies for HQ',
            'status' => 'open',
            'cur_loc' => $accountingDept->location_code,
        ]);

        $fakturPajak1 = AdditionalDocument::create([
            'type_id' => 8, // Faktur Pajak
            'document_number' => 'FP/2025/09/001',
            'document_date' => Carbon::now()->subDays(10),
            'po_no' => 'PO/2025/09/126', // Same PO as invoice1
            'project' => '000H',
            'receive_date' => Carbon::now()->subDays(9),
            'created_by' => $accountingManager->id,
            'remarks' => 'Tax invoice for office supplies',
            'status' => 'open',
            'cur_loc' => $accountingDept->location_code,
        ]);

        // Link additional documents to invoice1
        DB::table('additional_document_invoice')->insert([
            'invoice_id' => $invoice1->id,
            'additional_document_id' => $deliveryOrder1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('additional_document_invoice')->insert([
            'invoice_id' => $invoice1->id,
            'additional_document_id' => $fakturPajak1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Scenario 5: Invoice with linked additional documents
        $invoice2 = Invoice::create([
            'invoice_number' => 'INV/2025/09/002',
            'faktur_no' => 'FK-002-2025',
            'invoice_date' => Carbon::now()->subDays(8),
            'receive_date' => Carbon::now()->subDays(7),
            'supplier_id' => $supplier2->id,
            'po_no' => 'PO/2025/09/123', // Same PO as ITO1
            'receive_project' => '000H',
            'invoice_project' => '000H',
            'payment_project' => '001H',
            'currency' => 'IDR',
            'amount' => 7500000,
            'type_id' => 2, // Service
            'remarks' => 'Training scenario - Invoice with linked additional documents',
            'cur_loc' => $accountingDept->location_code,
            'status' => 'open',
            'created_by' => $accountingManager->id,
        ]);

        // Link invoice2 with ito1
        DB::table('additional_document_invoice')->insert([
            'invoice_id' => $invoice2->id,
            'additional_document_id' => $ito1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Scenario 6: Complex invoice with multiple supporting documents from different departments
        $invoice3 = Invoice::create([
            'invoice_number' => 'INV/2025/09/003',
            'faktur_no' => 'FK-003-2025',
            'invoice_date' => Carbon::now()->subDays(5),
            'receive_date' => Carbon::now()->subDays(4),
            'supplier_id' => $supplier2->id,
            'po_no' => 'PO/2025/09/127',
            'receive_project' => '000H',
            'invoice_project' => '021C',
            'payment_project' => '001H',
            'currency' => 'IDR',
            'amount' => 15000000,
            'type_id' => 2, // Service
            'remarks' => 'Training scenario - Monthly maintenance service',
            'cur_loc' => $accountingDept->location_code,
            'status' => 'open',
            'created_by' => $accountingManager->id,
        ]);

        // Create supporting documents from different departments
        $timeSheet1 = AdditionalDocument::create([
            'type_id' => 4, // Time Sheet
            'document_number' => 'TS/2025/09/001',
            'document_date' => Carbon::now()->subDays(7),
            'po_no' => 'PO/2025/09/127', // Same PO as invoice3
            'project' => '017C',
            'receive_date' => Carbon::now()->subDays(5),
            'created_by' => $warehouseStaff1->id,
            'remarks' => 'Service hours for equipment maintenance',
            'status' => 'open',
            'cur_loc' => $accountingDept->location_code,
        ]);

        $bast1 = AdditionalDocument::create([
            'type_id' => 10, // BAST
            'document_number' => 'BAST/2025/09/001',
            'document_date' => Carbon::now()->subDays(7),
            'po_no' => 'PO/2025/09/127', // Same PO as invoice3
            'project' => '021C',
            'receive_date' => Carbon::now()->subDays(5),
            'created_by' => $warehouseStaff2->id,
            'remarks' => 'Handover document for completed maintenance',
            'status' => 'open',
            'cur_loc' => $accountingDept->location_code,
        ]);

        // Link supporting documents to invoice3
        DB::table('additional_document_invoice')->insert([
            'invoice_id' => $invoice3->id,
            'additional_document_id' => $timeSheet1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('additional_document_invoice')->insert([
            'invoice_id' => $invoice3->id,
            'additional_document_id' => $bast1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create distribution scenarios

        // Scenario 6: Distribution in draft status
        $distribution1 = Distribution::create([
            'distribution_number' => '25/' . $logisticDept->location_code . '/DDS/0001',
            'type_id' => 1, // Normal
            'origin_department_id' => $logisticDept->id,
            'destination_department_id' => $accountingDept->id,
            'document_type' => 'additional_document',
            'created_by' => $logisticsManager->id,
            'status' => 'draft',
            'notes' => 'Training scenario - Distribution in draft status',
            'year' => 2025,
            'sequence' => 1,
        ]);

        // Link distribution1 with bapp1
        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution1->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $bapp1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Scenario 7: Distribution in sent status
        $distribution2 = Distribution::create([
            'distribution_number' => '25/' . $accountingDept->location_code . '/DDS/0001',
            'type_id' => 2, // Urgent
            'origin_department_id' => $accountingDept->id,
            'destination_department_id' => $financeDept->id,
            'document_type' => 'invoice',
            'created_by' => $accountingManager->id,
            'status' => 'sent',
            'sender_verified_at' => Carbon::now()->subDays(1),
            'sent_at' => Carbon::now()->subHours(12),
            'sender_verified_by' => $accountingManager->id,
            'notes' => 'Training scenario - Distribution in sent status',
            'year' => 2025,
            'sequence' => 1,
        ]);

        // Link distribution2 with invoice1
        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution2->id,
            'document_type' => Invoice::class,
            'document_id' => $invoice1->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update invoice1 location to reflect it's in transit
        $invoice1->update([
            'cur_loc' => 'in_transit',
        ]);

        // Scenario 8: Distribution with discrepancies
        $distribution3 = Distribution::create([
            'distribution_number' => '25/' . $wh021->location_code . '/DDS/0001',
            'type_id' => 3, // Express
            'origin_department_id' => $wh021->id,
            'destination_department_id' => $procurementDept->id,
            'document_type' => 'additional_document',
            'created_by' => $warehouseStaff2->id,
            'status' => 'received',
            'sender_verified_at' => Carbon::now()->subDays(2),
            'sent_at' => Carbon::now()->subDays(2),
            'received_at' => Carbon::now()->subDays(1),
            'sender_verified_by' => $warehouseStaff2->id,
            'has_discrepancies' => true,
            'notes' => 'Training scenario - Distribution with discrepancies',
            'year' => 2025,
            'sequence' => 1,
        ]);

        // Link distribution3 with goodsIssue1
        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution3->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $goodsIssue1->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'receiver_verified' => true,
            'receiver_verification_status' => 'damaged',
            'receiver_verification_notes' => 'Document appears to be water damaged',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update goodsIssue1 location
        $goodsIssue1->update([
            'cur_loc' => $procurementDept->location_code,
        ]);

        // Scenario 9: Distribution ready to be received by Accounting
        $distribution4 = Distribution::create([
            'distribution_number' => '25/' . $logisticDept->location_code . '/DDS/0002',
            'type_id' => 1, // Normal
            'origin_department_id' => $logisticDept->id,
            'destination_department_id' => $accountingDept->id,
            'document_type' => 'additional_document',
            'created_by' => $logisticsManager->id,
            'status' => 'sent',
            'sender_verified_at' => Carbon::now()->subHours(6),
            'sent_at' => Carbon::now()->subHours(5),
            'sender_verified_by' => $logisticsManager->id,
            'notes' => 'Training scenario - Distribution ready to be received by Accounting',
            'year' => 2025,
            'sequence' => 2,
        ]);

        // Create a new additional document for this distribution
        $deliveryOrder2 = AdditionalDocument::create([
            'type_id' => 9, // Delivery Order
            'document_number' => 'DO/2025/09/002',
            'document_date' => Carbon::now()->subDays(2),
            'po_no' => 'PO/2025/09/128',
            'project' => '000H',
            'receive_date' => Carbon::now()->subDays(1),
            'created_by' => $logisticsManager->id,
            'remarks' => 'Delivery of IT equipment',
            'status' => 'open',
            'cur_loc' => 'in_transit',
        ]);

        // Link distribution4 with deliveryOrder2
        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution4->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $deliveryOrder2->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create only the receive flow scenario
     */
    private function createReceiveFlowScenario($logisticsManager, $logisticDept, $accountingDept)
    {
        // Check if the document already exists
        $existingDoc = AdditionalDocument::where('document_number', 'DO/2025/09/002')->first();
        if ($existingDoc) {
            // If it exists, just update its location to in_transit
            $existingDoc->update(['cur_loc' => 'in_transit']);
            $deliveryOrder2 = $existingDoc;
        } else {
            // Create a new additional document for the receive flow scenario
            $deliveryOrder2 = AdditionalDocument::create([
                'type_id' => 9, // Delivery Order
                'document_number' => 'DO/2025/09/002',
                'document_date' => Carbon::now()->subDays(2),
                'po_no' => 'PO/2025/09/128',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDays(1),
                'created_by' => $logisticsManager->id,
                'remarks' => 'Delivery of IT equipment',
                'status' => 'open',
                'cur_loc' => 'in_transit',
            ]);
        }

        // Check if the distribution already exists
        $existingDist = Distribution::where('distribution_number', '25/' . $logisticDept->location_code . '/DDS/0002')->first();
        if ($existingDist) {
            // If it exists, just update its status to sent
            $existingDist->update([
                'status' => 'sent',
                'sender_verified_at' => Carbon::now()->subHours(6),
                'sent_at' => Carbon::now()->subHours(5)
            ]);
            return;
        }

        // Scenario 9: Distribution ready to be received by Accounting
        $distribution4 = Distribution::create([
            'distribution_number' => '25/' . $logisticDept->location_code . '/DDS/0002',
            'type_id' => 1, // Normal
            'origin_department_id' => $logisticDept->id,
            'destination_department_id' => $accountingDept->id,
            'document_type' => 'additional_document',
            'created_by' => $logisticsManager->id,
            'status' => 'sent',
            'sender_verified_at' => Carbon::now()->subHours(6),
            'sent_at' => Carbon::now()->subHours(5),
            'sender_verified_by' => $logisticsManager->id,
            'notes' => 'Training scenario - Distribution ready to be received by Accounting',
            'year' => 2025,
            'sequence' => 2,
        ]);

        // Link distribution4 with deliveryOrder2
        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution4->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $deliveryOrder2->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a distribution with missing/damaged documents scenario
        $this->createDiscrepancyScenario($logisticsManager, $logisticDept, $accountingDept);
    }

    /**
     * Create a scenario with missing/damaged documents
     */
    private function createDiscrepancyScenario($logisticsManager, $logisticDept, $accountingDept)
    {
        // Check if the documents already exist
        $existingDoc1 = AdditionalDocument::where('document_number', 'ITO/2025/09/003')->first();
        $existingDoc2 = AdditionalDocument::where('document_number', 'BAPP/2025/09/003')->first();
        $existingDoc3 = AdditionalDocument::where('document_number', 'GI/2025/09/003')->first();

        if ($existingDoc1 && $existingDoc2 && $existingDoc3) {
            // If they exist, just update their locations to in_transit
            $existingDoc1->update(['cur_loc' => 'in_transit']);
            $existingDoc2->update(['cur_loc' => 'in_transit']);
            $existingDoc3->update(['cur_loc' => 'in_transit']);
            $ito3 = $existingDoc1;
            $bapp3 = $existingDoc2;
            $goodsIssue3 = $existingDoc3;
        } else {
            // Create new additional documents for the discrepancy scenario
            $ito3 = AdditionalDocument::create([
                'type_id' => 1, // ITO
                'document_number' => 'ITO/2025/09/003',
                'document_date' => Carbon::now()->subDays(3),
                'po_no' => 'PO/2025/09/130',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDays(2),
                'created_by' => $logisticsManager->id,
                'remarks' => 'Transfer of materials for project',
                'status' => 'open',
                'cur_loc' => 'in_transit',
                'ito_creator' => $logisticsManager->name,
                'origin_wh' => 'WH017',
                'destination_wh' => 'WH021',
            ]);

            $bapp3 = AdditionalDocument::create([
                'type_id' => 3, // BAPP
                'document_number' => 'BAPP/2025/09/003',
                'document_date' => Carbon::now()->subDays(3),
                'po_no' => 'PO/2025/09/130',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDays(2),
                'created_by' => $logisticsManager->id,
                'remarks' => 'Acceptance document for materials',
                'status' => 'open',
                'cur_loc' => 'in_transit',
            ]);

            $goodsIssue3 = AdditionalDocument::create([
                'type_id' => 2, // Goods Issue
                'document_number' => 'GI/2025/09/003',
                'document_date' => Carbon::now()->subDays(3),
                'po_no' => 'PO/2025/09/130',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDays(2),
                'created_by' => $logisticsManager->id,
                'remarks' => 'Issue of materials for project',
                'status' => 'open',
                'cur_loc' => 'in_transit',
            ]);
        }

        // Check if the distribution already exists
        $existingDist = Distribution::where('distribution_number', '25/' . $logisticDept->location_code . '/DDS/0003')->first();
        if ($existingDist) {
            // If it exists, just update its status to sent
            $existingDist->update([
                'status' => 'sent',
                'sender_verified_at' => Carbon::now()->subHours(4),
                'sent_at' => Carbon::now()->subHours(3)
            ]);
            return;
        }

        // Create a distribution with multiple documents for the discrepancy scenario
        $distribution5 = Distribution::create([
            'distribution_number' => '25/' . $logisticDept->location_code . '/DDS/0003',
            'type_id' => 1, // Normal
            'origin_department_id' => $logisticDept->id,
            'destination_department_id' => $accountingDept->id,
            'document_type' => 'additional_document',
            'created_by' => $logisticsManager->id,
            'status' => 'sent',
            'sender_verified_at' => Carbon::now()->subHours(4),
            'sent_at' => Carbon::now()->subHours(3),
            'sender_verified_by' => $logisticsManager->id,
            'notes' => 'Training scenario - Distribution with missing/damaged documents',
            'year' => 2025,
            'sequence' => 3,
        ]);

        // Link the documents to the distribution
        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution5->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $ito3->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution5->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $bapp3->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution5->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $goodsIssue3->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create the replacement document scenario
        $this->createReplacementScenario($logisticsManager, $logisticDept, $accountingDept);
    }

    /**
     * Create a scenario with replacement documents
     */
    private function createReplacementScenario($logisticsManager, $logisticDept, $accountingDept)
    {
        // Check if the replacement document already exists
        $existingDoc = AdditionalDocument::where('document_number', 'BAPP/2025/09/003R')->first();
        if ($existingDoc) {
            // If it exists, just update its location to the logistics department
            $existingDoc->update(['cur_loc' => $logisticDept->location_code]);
            $replacementBapp = $existingDoc;
        } else {
            // Create a replacement document for the damaged BAPP
            $replacementBapp = AdditionalDocument::create([
                'type_id' => 3, // BAPP
                'document_number' => 'BAPP/2025/09/003R', // R for Replacement
                'document_date' => Carbon::now()->subDay(),
                'po_no' => 'PO/2025/09/130',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDay(),
                'created_by' => $logisticsManager->id,
                'remarks' => 'Replacement for damaged BAPP/2025/09/003',
                'status' => 'open',
                'cur_loc' => $logisticDept->location_code,
            ]);
        }

        // Check if the replacement document for the missing document already exists
        $existingDoc2 = AdditionalDocument::where('document_number', 'GI/2025/09/003R')->first();
        if ($existingDoc2) {
            // If it exists, just update its location to the logistics department
            $existingDoc2->update(['cur_loc' => $logisticDept->location_code]);
            $replacementGI = $existingDoc2;
        } else {
            // Create a replacement document for the missing Goods Issue
            $replacementGI = AdditionalDocument::create([
                'type_id' => 2, // Goods Issue
                'document_number' => 'GI/2025/09/003R', // R for Replacement
                'document_date' => Carbon::now()->subDay(),
                'po_no' => 'PO/2025/09/130',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDay(),
                'created_by' => $logisticsManager->id,
                'remarks' => 'Replacement for missing GI/2025/09/003',
                'status' => 'open',
                'cur_loc' => $logisticDept->location_code,
            ]);
        }

        // Check if the replacement distribution already exists
        $existingDist = Distribution::where('distribution_number', '25/' . $logisticDept->location_code . '/DDS/0004')->first();
        if ($existingDist) {
            // If it exists, just update its status to draft
            $existingDist->update([
                'status' => 'draft',
                'notes' => 'Replacement documents for distribution 25/' . $logisticDept->location_code . '/DDS/0003'
            ]);
            return;
        }

        // Create a draft distribution for the replacement documents
        $replacementDistribution = Distribution::create([
            'distribution_number' => '25/' . $logisticDept->location_code . '/DDS/0004',
            'type_id' => 2, // Urgent
            'origin_department_id' => $logisticDept->id,
            'destination_department_id' => $accountingDept->id,
            'document_type' => 'additional_document',
            'created_by' => $logisticsManager->id,
            'status' => 'draft',
            'sender_verified_by' => null,
            'sender_verified_at' => null,
            'sent_at' => null,
            'notes' => 'Replacement documents for distribution 25/' . $logisticDept->location_code . '/DDS/0003',
            'year' => 2025,
            'sequence' => 4,
        ]);

        // Link the replacement documents to the distribution
        DB::table('distribution_documents')->insert([
            'distribution_id' => $replacementDistribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $replacementBapp->id,
            'sender_verified' => false,
            'sender_verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('distribution_documents')->insert([
            'distribution_id' => $replacementDistribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $replacementGI->id,
            'sender_verified' => false,
            'sender_verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a scenario where Accounting sends 3 documents to Finance but only 2 are received
     */
    private function createAccountingToFinanceScenario($accountingManager, $financeManager, $accountingDept, $financeDept)
    {
        // Check if the documents already exist
        $existingDoc1 = AdditionalDocument::where('document_number', 'TAX/2025/09/001')->first();
        $existingDoc2 = AdditionalDocument::where('document_number', 'RECEIPT/2025/09/001')->first();
        $existingDoc3 = AdditionalDocument::where('document_number', 'APPROVAL/2025/09/001')->first();

        if ($existingDoc1 && $existingDoc2 && $existingDoc3) {
            // If they exist, just update their locations
            $existingDoc1->update(['cur_loc' => 'in_transit']);
            $existingDoc2->update(['cur_loc' => 'in_transit']);
            $existingDoc3->update(['cur_loc' => 'in_transit']);
            $taxDoc = $existingDoc1;
            $receiptDoc = $existingDoc2;
            $approvalDoc = $existingDoc3;
        } else {
            // Create new additional documents for the scenario
            $taxDoc = AdditionalDocument::create([
                'type_id' => 7, // Tax Document
                'document_number' => 'TAX/2025/09/001',
                'document_date' => Carbon::now()->subDays(1),
                'po_no' => 'PO/2025/09/150',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDays(1),
                'created_by' => $accountingManager->id,
                'remarks' => 'Tax document for project payment',
                'status' => 'open',
                'cur_loc' => 'in_transit',
            ]);

            $receiptDoc = AdditionalDocument::create([
                'type_id' => 8, // Receipt
                'document_number' => 'RECEIPT/2025/09/001',
                'document_date' => Carbon::now()->subDays(1),
                'po_no' => 'PO/2025/09/150',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDays(1),
                'created_by' => $accountingManager->id,
                'remarks' => 'Receipt for project payment',
                'status' => 'open',
                'cur_loc' => 'in_transit',
            ]);

            $approvalDoc = AdditionalDocument::create([
                'type_id' => 10, // Approval Document
                'document_number' => 'APPROVAL/2025/09/001',
                'document_date' => Carbon::now()->subDays(1),
                'po_no' => 'PO/2025/09/150',
                'project' => '000H',
                'receive_date' => Carbon::now()->subDays(1),
                'created_by' => $accountingManager->id,
                'remarks' => 'Approval for project payment',
                'status' => 'open',
                'cur_loc' => 'in_transit',
            ]);
        }

        // Check if the distribution already exists
        $existingDist = Distribution::where('distribution_number', '25/' . $accountingDept->location_code . '/DDS/0005')->first();
        if ($existingDist) {
            // If it exists, just update its status to sent
            $existingDist->update([
                'status' => 'sent',
                'sender_verified_at' => Carbon::now()->subHours(2),
                'sent_at' => Carbon::now()->subHours(1)
            ]);
            return;
        }

        // Create a distribution from Accounting to Finance with 3 documents
        $distribution = Distribution::create([
            'distribution_number' => '25/' . $accountingDept->location_code . '/DDS/0005',
            'type_id' => 1, // Normal
            'origin_department_id' => $accountingDept->id,
            'destination_department_id' => $financeDept->id,
            'document_type' => 'additional_document',
            'created_by' => $accountingManager->id,
            'status' => 'sent',
            'sender_verified_at' => Carbon::now()->subHours(2),
            'sent_at' => Carbon::now()->subHours(1),
            'sender_verified_by' => $accountingManager->id,
            'notes' => 'Training scenario - Distribution with a document that will go missing',
            'year' => 2025,
            'sequence' => 5,
        ]);

        // Link the documents to the distribution
        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $taxDoc->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $receiptDoc->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('distribution_documents')->insert([
            'distribution_id' => $distribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $approvalDoc->id,
            'sender_verified' => true,
            'sender_verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a replacement document for the one that will go missing
        $this->createReplacementApprovalScenario($accountingManager, $accountingDept, $financeDept, $approvalDoc);
    }

    /**
     * Create a scenario with a replacement approval document
     */
    private function createReplacementApprovalScenario($accountingManager, $accountingDept, $financeDept, $originalDoc)
    {
        // Check if the replacement document already exists
        $existingDoc = AdditionalDocument::where('document_number', 'APPROVAL/2025/09/001R')->first();
        if ($existingDoc) {
            // If it exists, just update its location to the accounting department
            $existingDoc->update(['cur_loc' => $accountingDept->location_code]);
            $replacementApproval = $existingDoc;
        } else {
            // Create a replacement document for the missing approval
            $replacementApproval = AdditionalDocument::create([
                'type_id' => 10, // Approval Document
                'document_number' => 'APPROVAL/2025/09/001R', // R for Replacement
                'document_date' => Carbon::now(),
                'po_no' => 'PO/2025/09/150',
                'project' => '000H',
                'receive_date' => Carbon::now(),
                'created_by' => $accountingManager->id,
                'remarks' => 'Replacement for missing APPROVAL/2025/09/001',
                'status' => 'open',
                'cur_loc' => $accountingDept->location_code,
            ]);
        }

        // Check if the replacement distribution already exists
        $existingDist = Distribution::where('distribution_number', '25/' . $accountingDept->location_code . '/DDS/0006')->first();
        if ($existingDist) {
            // If it exists, just update its status to draft
            $existingDist->update([
                'status' => 'draft',
                'notes' => 'Replacement document for distribution 25/' . $accountingDept->location_code . '/DDS/0005'
            ]);
            return;
        }

        // Create a draft distribution for the replacement document
        $replacementDistribution = Distribution::create([
            'distribution_number' => '25/' . $accountingDept->location_code . '/DDS/0006',
            'type_id' => 2, // Urgent
            'origin_department_id' => $accountingDept->id,
            'destination_department_id' => $financeDept->id,
            'document_type' => 'additional_document',
            'created_by' => $accountingManager->id,
            'status' => 'draft',
            'sender_verified_by' => null,
            'sender_verified_at' => null,
            'sent_at' => null,
            'notes' => 'Replacement document for distribution 25/' . $accountingDept->location_code . '/DDS/0005',
            'year' => 2025,
            'sequence' => 6,
        ]);

        // Link the replacement document to the distribution
        DB::table('distribution_documents')->insert([
            'distribution_id' => $replacementDistribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $replacementApproval->id,
            'sender_verified' => false,
            'sender_verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
