<?php

require __DIR__.'/../vendor/autoload.php';

$files = [
    'day1' => 'C:/Users/aoi/Downloads/day1_202607242200.pdf',
    'day2' => 'C:/Users/aoi/Downloads/day2_202607241900.pdf',
    'day3' => 'C:/Users/aoi/Downloads/day3_202607241900.pdf',
];

$parser = new Smalot\PdfParser\Parser();

foreach ($files as $label => $path) {
    echo "=== {$label} ===\n";
    if (! file_exists($path)) {
        echo "NOT FOUND\n\n";
        continue;
    }

    try {
        $pdf = $parser->parseFile($path);
        $text = trim($pdf->getText());
        echo 'LENGTH: '.strlen($text)."\n";
        echo $text !== '' ? $text : '[empty - likely image PDF]';
        echo "\n\n";
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n\n";
    }
}
