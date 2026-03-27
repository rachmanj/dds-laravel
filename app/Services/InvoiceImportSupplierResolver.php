<?php

namespace App\Services;

use App\Models\Supplier;

class InvoiceImportSupplierResolver
{
    /**
     * @return array{supplier_id: int|null, candidates: array<int, array{id: int, name: string, score: float}>}
     */
    public function resolve(?string $supplierNameRaw): array
    {
        if ($supplierNameRaw === null || trim($supplierNameRaw) === '') {
            return ['supplier_id' => null, 'candidates' => []];
        }

        $normalized = $this->normalize($supplierNameRaw);
        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name']);

        $exact = $suppliers->first(fn ($s) => $this->normalize($s->name) === $normalized);
        if ($exact) {
            return [
                'supplier_id' => $exact->id,
                'candidates' => [['id' => $exact->id, 'name' => $exact->name, 'score' => 1.0]],
            ];
        }

        $candidates = [];
        foreach ($suppliers as $s) {
            similar_text($normalized, $this->normalize($s->name), $pct);
            $score = $pct / 100;
            if ($score >= 0.72) {
                $candidates[] = ['id' => $s->id, 'name' => $s->name, 'score' => round($score, 3)];
            }
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        $candidates = array_slice($candidates, 0, 5);

        if (count($candidates) === 1 && $candidates[0]['score'] >= 0.88) {
            return [
                'supplier_id' => $candidates[0]['id'],
                'candidates' => $candidates,
            ];
        }

        if (count($candidates) >= 1 && $candidates[0]['score'] >= 0.92) {
            return [
                'supplier_id' => $candidates[0]['id'],
                'candidates' => $candidates,
            ];
        }

        return [
            'supplier_id' => null,
            'candidates' => $candidates,
        ];
    }

    private function normalize(string $name): string
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return trim($name);
    }
}
