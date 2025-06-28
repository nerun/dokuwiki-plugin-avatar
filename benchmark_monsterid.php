#!/usr/bin/php
<?php
// -*- coding: utf-8 -*-
declare(strict_types=1);

// Avatar parameters
define('IMG_SIZE', 120);
define('ROUNDS', 20000);
define('PARTS_DIR', __DIR__ . '/parts');

$partGroups = [
    'arms'  => 5,
    'body'  => 15,
    'eyes'  => 15,
    'hair'  => 5,
    'legs'  => 5,
    'mouth' => 10,
];

function getRandomPart(string $group, int $max): GdImage {
    $n = random_int(1, $max);
    $path = sprintf('%s/%s_%d.png', PARTS_DIR, $group, $n);
    return imagecreatefrompng($path);
}

function generateMonster(): GdImage {
    $img = imagecreatetruecolor(IMG_SIZE, IMG_SIZE);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    global $partGroups;
    foreach ($partGroups as $group => $count) {
        $part = getRandomPart($group, $count);
        imagecopy($img, $part, 0, 0, 0, 0, IMG_SIZE, IMG_SIZE);
        imagedestroy($part);
    }
    return $img;
}

// Run benchmark
$sizes = [];
for ($i = 0; $i < ROUNDS; $i++) {
    $img = generateMonster();
    ob_start();
    imagepng($img, null, 9);
    $data = ob_get_clean();
    $sizes[] = strlen($data);
    imagedestroy($img);
}

// Statistics
$min = min($sizes);
$max = max($sizes);
$avg = array_sum($sizes) / count($sizes);
$stddev = sqrt(array_sum(array_map(fn($s) => pow($s - $avg, 2), $sizes)) / count($sizes));

printf("Generated avatars: %d\n", ROUNDS);
printf("Minimum size: %.2f KB\n", $min / 1024);
printf("Maximum size: %.2f KB\n", $max / 1024);
printf("Average size: %.2f KB\n", $avg / 1024);
printf("Standard deviation: %.2f KB\n", $stddev / 1024);

