<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupInvoiceImports extends Command
{
    protected $signature = 'invoice-import:cleanup {--hours=24 : Delete temp files older than this many hours}';

    protected $description = 'Remove stale invoice import temp files from storage';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours)->getTimestamp();
        $disk = Storage::disk('local');
        $dir = 'temp/invoice-imports';

        if (! $disk->exists($dir)) {
            $this->info('No temp import directory.');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($disk->files($dir) as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} stale file(s).");

        return self::SUCCESS;
    }
}
