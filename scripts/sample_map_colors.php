<?php

$path = __DIR__.'/../public/images/tif2026-map.png';
$img = imagecreatefrompng($path);
$w = imagesx($img);
$h = imagesy($img);
echo "Size: {$w}x{$h}\n";

// Grid sample across map to find vibrant logo colors
$regions = [
    'hot-stage' => ['x' => 640, 'y' => 500, 'w' => 120, 'h' => 80],
    'heat-garage' => ['x' => 480, 'y' => 560, 'w' => 120, 'h' => 80],
    'smile-garden' => ['x' => 1140, 'y' => 340, 'w' => 120, 'h' => 80],
    'doll-factory' => ['x' => 1340, 'y' => 480, 'w' => 120, 'h' => 80],
    'sky-stage' => ['x' => 1240, 'y' => 240, 'w' => 120, 'h' => 80],
    'info-centre' => ['x' => 1100, 'y' => 580, 'w' => 120, 'h' => 80],
    'torocco-park' => ['x' => 340, 'y' => 740, 'w' => 120, 'h' => 80],
    'ukishima-stage' => ['x' => 580, 'y' => 780, 'w' => 120, 'h' => 80],
];

function toHex(int $r, int $g, int $b): string
{
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

foreach ($regions as $slug => $region) {
    $colors = [];
    for ($y = $region['y']; $y < $region['y'] + $region['h']; $y += 4) {
        for ($x = $region['x']; $x < $region['x'] + $region['w']; $x += 4) {
            $rgb = imagecolorat($img, $x, $y);
            $a = ($rgb >> 24) & 0x7F;
            if ($a > 100) {
                continue;
            }
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
            $sat = $max - $min;
            if ($sat < 40 || $max < 80) {
                continue;
            }
            $key = toHex($r, $g, $b);
            $colors[$key] = ($colors[$key] ?? 0) + 1;
        }
    }
    arsort($colors);
    $top = array_slice(array_keys($colors), 0, 5);
    echo "{$slug}: ".implode(', ', $top)."\n";
}
