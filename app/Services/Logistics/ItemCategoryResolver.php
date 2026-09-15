<?php

namespace App\Services\Logistics;

use App\Models\LogisticsItemCategory;

class ItemCategoryResolver
{
    public const FALLBACK_CATEGORY = '(tanpa kategori)';

    /** @var array<string, string>|null */
    private ?array $prefixMap = null;

    public function resolve(string $itemCode): string
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return self::FALLBACK_CATEGORY;
        }

        $upper = strtoupper($itemCode);
        $map = $this->prefixMap();

        if (isset($map[$upper])) {
            return $map[$upper];
        }

        $hyphenPos = strpos($upper, '-');
        if ($hyphenPos !== false) {
            $segment = substr($upper, 0, $hyphenPos);
            if (isset($map[$segment])) {
                return $map[$segment];
            }
        }

        $bare = $hyphenPos !== false ? substr($upper, 0, $hyphenPos) : $upper;

        foreach ([3, 2] as $length) {
            if (strlen($bare) < $length) {
                continue;
            }

            $prefix = substr($bare, 0, $length);
            if (isset($map[$prefix])) {
                return $map[$prefix];
            }
        }

        return self::FALLBACK_CATEGORY;
    }

    /**
     * @return array<string, string>
     */
    private function prefixMap(): array
    {
        if ($this->prefixMap !== null) {
            return $this->prefixMap;
        }

        $this->prefixMap = LogisticsItemCategory::query()
            ->where('is_active', true)
            ->pluck('category', 'prefix')
            ->mapWithKeys(fn (string $category, string $prefix): array => [strtoupper($prefix) => $category])
            ->all();

        return $this->prefixMap;
    }
}
