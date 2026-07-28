<?php

namespace App\Console\Commands;

use App\Services\TimetableImportService;
use Illuminate\Console\Command;

class ImportTimetableCsv extends Command
{
    protected $signature = 'tif:import-csv {path : CSV file path} {--replace-day : Replace all performances for days in CSV}';

    protected $description = 'Import TIF timetable performances from CSV';

    public function handle(TimetableImportService $importService): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $result = $importService->importFromCsv($path, (bool) $this->option('replace-day'));

        $this->info("Imported: {$result['imported']}");
        $this->info("Skipped: {$result['skipped']}");

        if (! empty($result['errors'])) {
            $this->warn('Errors:');
            foreach ($result['errors'] as $line => $message) {
                $this->line("  Line {$line}: {$message}");
            }
        }

        return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
