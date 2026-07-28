<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Performance;
use App\Services\TimetableImportService;

$day = '2026-08-01';
$importService = app(TimetableImportService::class);
$dataPath = database_path('data');
$files = glob("{$dataPath}/tif2026_day2_*.csv");
sort($files);

Performance::query()->whereDate('day', $day)->delete();
echo "Cleared existing DAY2 performances.\n";

$total = 0;
foreach ($files as $index => $csvFile) {
    $result = $importService->importFromCsv($csvFile, replaceDay: false);
    echo basename($csvFile).": imported {$result['imported']}\n";
    $total += $result['imported'];
}

$count = Performance::query()->whereDate('day', $day)->count();
echo "Total imported: {$total}, DB count: {$count}\n";
