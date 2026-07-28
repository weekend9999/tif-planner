<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Performance;
use App\Models\Stage;

$days = Performance::query()->selectRaw('day, count(*) as c')->groupBy('day')->pluck('c', 'day');
echo "By day:\n";
foreach ($days as $day => $c) {
    echo "  {$day}: {$c}\n";
}

$day = '2026-08-01';
$count = Performance::query()->where('day', $day)->count();
echo "DAY2 (2026-08-01) total: {$count}\n";
