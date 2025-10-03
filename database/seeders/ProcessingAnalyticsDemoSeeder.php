<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\AdditionalDocument;
use Carbon\Carbon;

class ProcessingAnalyticsDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample invoices with different receive dates
        for ($i = 1; $i <= 10; $i++) {
            $receiveDate = Carbon::now()->subDays($i);
            Invoice::create([
                'invoice_number' => 'INV-DEMO-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'invoice_date' => $receiveDate->format('Y-m-d'),
                'receive_date' => $receiveDate->format('Y-m-d'),
                'supplier_id' => ($i % 3) + 1,
                'amount' => rand(1000000, 5000000),
                'currency' => 'IDR',
                'type_id' => ($i % 3) + 1,
                'created_by' => ($i % 2) + 1, // Alternate between departments 1 and 2
                'status' => 'open',
                'distribution_status' => 'available'
            ]);
        }

        // Create sample additional documents
        for ($i = 1; $i <= 8; $i++) {
            $receiveDate = Carbon::now()->subDays($i + 2);
            AdditionalDocument::create([
                'document_number' => 'DOC-DEMO-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'document_date' => $receiveDate->format('Y-m-d'),
                'receive_date' => $receiveDate->format('Y-m-d'),
                'type_id' => ($i % 3) + 1,
                'created_by' => ($i % 2) + 1, // Alternate between departments 1 and 2
                'status' => 'open',
                'distribution_status' => 'available'
            ]);
        }

        $this->command->info('Sample data for Processing Analytics created successfully!');
    }
}
