<?php

namespace App\Services;

use App\Models\AdditionalDocument;
use App\Models\Distribution;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class DocumentJourneyService
{
    /**
     * @return array{
     *     document: Invoice|AdditionalDocument,
     *     distributions: Collection<int, Distribution>,
     *     stats: array<string, mixed>,
     *     departmentTimeStats: array<int, array<string, mixed>>,
     *     documentType: string
     * }
     */
    public function build(string $documentType, int $documentId): array
    {
        if (! in_array($documentType, ['invoice', 'additional-document'], true)) {
            abort(404);
        }

        if ($documentType === 'invoice') {
            $document = Invoice::with(['supplier', 'distributions.originDepartment', 'distributions.destinationDepartment'])->findOrFail($documentId);
            $modelClass = Invoice::class;
        } else {
            $document = AdditionalDocument::with(['type', 'distributions.originDepartment', 'distributions.destinationDepartment'])->findOrFail($documentId);
            $modelClass = AdditionalDocument::class;
        }

        $distributions = Distribution::whereHas('documents', function ($query) use ($modelClass, $documentId) {
            $query->where('document_type', $modelClass)
                ->where('document_id', $documentId);
        })->with(['originDepartment', 'destinationDepartment', 'histories.user', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_distributions' => $distributions->count(),
            'total_departments_visited' => $distributions->pluck('destination_department_id')->unique()->count(),
            'current_location' => $document->cur_loc ?? 'N/A',
            'current_status' => $document->distribution_status ?? 'available',
        ];

        $departmentTimeStats = [];

        foreach ($distributions as $distribution) {
            $deptName = $distribution->destinationDepartment->name;
            $deptId = $distribution->destinationDepartment->id;

            if (! isset($departmentTimeStats[$deptId])) {
                $departmentTimeStats[$deptId] = [
                    'name' => $deptName,
                    'total_time' => 0,
                    'visits' => 0,
                    'first_visit' => null,
                    'last_visit' => null,
                ];
            }

            $departmentTimeStats[$deptId]['visits']++;

            if ($distribution->received_at && $distribution->sent_at) {
                $timeSpent = $distribution->sent_at->diffInDays($distribution->received_at);
                $departmentTimeStats[$deptId]['total_time'] += $timeSpent;
            }

            if (! $departmentTimeStats[$deptId]['first_visit'] || $distribution->created_at < $departmentTimeStats[$deptId]['first_visit']) {
                $departmentTimeStats[$deptId]['first_visit'] = $distribution->created_at;
            }
            if (! $departmentTimeStats[$deptId]['last_visit'] || $distribution->created_at > $departmentTimeStats[$deptId]['last_visit']) {
                $departmentTimeStats[$deptId]['last_visit'] = $distribution->created_at;
            }
        }

        foreach ($departmentTimeStats as &$dept) {
            $dept['avg_time'] = $dept['visits'] > 0 ? round($dept['total_time'] / $dept['visits'], 1) : 0;
        }
        unset($dept);

        if ($distributions->count() > 0) {
            $firstDistribution = $distributions->last();
            $stats['journey_start'] = $firstDistribution->created_at;
            $stats['journey_duration'] = $firstDistribution->created_at->diffInDays(now());
            $stats['total_distance'] = $distributions->count() - 1;
            $stats['avg_time_per_department'] = $distributions->count() > 0 ?
                round($stats['journey_duration'] / $distributions->count(), 1) : 0;
        }

        return [
            'document' => $document,
            'distributions' => $distributions,
            'stats' => $stats,
            'departmentTimeStats' => $departmentTimeStats,
            'documentType' => $documentType,
        ];
    }
}
