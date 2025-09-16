<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdditionalDocument;
use App\Models\Distribution;
use App\Models\User;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingToFinanceScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users for creating documents
        $accountingManager = User::where('username', 'accounting_manager')->first() ?? User::where('email', 'acc@ninja.com')->first();
        $financeManager = User::where('username', 'finance_manager')->first() ?? User::where('email', 'fin@ninja.com')->first();

        if (!$accountingManager || !$financeManager) {
            $this->command->error('Required users not found.');
            return;
        }

        // Get departments for scenarios
        $accountingDept = Department::where('akronim', 'ACC')->first();
        $financeDept = Department::where('akronim', 'FIN')->first();

        if (!$accountingDept || !$financeDept) {
            $this->command->error('Required departments not found.');
            return;
        }

        $this->createAccountingToFinanceScenario($accountingManager, $financeManager, $accountingDept, $financeDept);
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
            $this->command->info('Updated existing distribution: ' . $existingDist->distribution_number);

            // Create replacement document if it doesn't exist
            $this->createReplacementApprovalScenario($accountingManager, $accountingDept, $financeDept, $approvalDoc);
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

        $this->command->info('Created new distribution: ' . $distribution->distribution_number);

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
            $this->command->info('Updated existing replacement document: ' . $existingDoc->document_number);
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
            $this->command->info('Created new replacement document: ' . $replacementApproval->document_number);
        }

        // Check if the replacement distribution already exists
        $existingDist = Distribution::where('distribution_number', '25/' . $accountingDept->location_code . '/DDS/0006')->first();
        if ($existingDist) {
            // If it exists, just update its status to draft
            $existingDist->update([
                'status' => 'draft',
                'notes' => 'Replacement document for distribution 25/' . $accountingDept->location_code . '/DDS/0005'
            ]);
            $this->command->info('Updated existing replacement distribution: ' . $existingDist->distribution_number);
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

        $this->command->info('Created new replacement distribution: ' . $replacementDistribution->distribution_number);
    }
}
