<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InspectDatabase extends Command
{
    protected $signature = 'db:inspect {--open : Write snapshot and print path}';

    protected $description = 'Export readable DB snapshot for editor viewing (fallback when SQLite Viewer fails)';

    public function handle(): int
    {
        $tables = [
            'users',
            'watch_plans',
            'watch_plan_items',
            'favorite_artists',
            'performances',
        ];

        $output = "# TIF Planner DB snapshot\n";
        $output .= '# Generated: '.now()->toDateTimeString()."\n\n";

        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            $rows = DB::table($table)->limit(50)->get();
            $output .= "## {$table} (".DB::table($table)->count()." rows, showing up to 50)\n\n";
            $output .= json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
        }

        $path = database_path('db-inspect.txt');
        File::put($path, $output);

        $this->info("Wrote {$path}");
        $this->line('Open this text file in Cursor if database.sqlite shows as binary.');

        return self::SUCCESS;
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
