<?php
/**
 * MonsterID Generator for DokuWiki Avatar Plugin
 * 
 * Generates identicon-style monster avatars based on a seed.
 * Requires PHP GD extension.
 * 
 * 
 * MIT License
 * 
 * Copyright (c) 2007 Andreas Gohr <andi@splitbrain.org>
 *     @source: https://github.com/splitbrain/monsterID
 *
 * Copyright (c) 2009 Gina Häußge <github.com/foosel>
 *     @source: https://github.com/dokufreaks/plugin-avatar
 *
 * Copyright (c) 2025 Daniel Dias Rodrigues <danieldiasr@gmail.com>
 *     @source: https://github.com/nerun/dokuwiki-plugin-avatar
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    $seed = preg_replace('/[^a-f0-9]/i', '', $_GET['seed'] ?? '');
    $size = (int) ($_GET['size'] ?? 120);
    $size = max(20, min(400, $size)); // limits between 20 and 400 pixels

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');

    $image = generate_monster($seed, $size);
    if ($image) {
        imagepng($image);
        imagedestroy($image);
    } else {
        http_response_code(500);
        echo 'Error generating image.';
    }
    exit;
}

/**
 * Generates monster image based on seed and size
 */
function generate_monster(string $seed, int $size): ?GdImage
{
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }

    $hash = md5($seed);

    $parts = [
        'legs'  => get_part(substr($hash, 0, 2), 1, 5),
        'hair'  => get_part(substr($hash, 2, 2), 1, 5),
        'arms'  => get_part(substr($hash, 4, 2), 1, 5),
        'body'  => get_part(substr($hash, 6, 2), 1, 15),
        'eyes'  => get_part(substr($hash, 8, 2), 1, 15),
        'mouth' => get_part(substr($hash, 10, 2), 1, 10),
    ];

    $monster = imagecreatetruecolor(120, 120);
    if (!$monster) return null;

    $white = @imagecolorallocate($monster, 255, 255, 255);
    imagefill($monster, 0, 0, $white);

    foreach ($parts as $part => $index) {
        $filename = __DIR__ . '/parts/' . $part . '_' . $index . '.png';
        if (!file_exists($filename)) continue;
        $part_img = imagecreatefrompng($filename);
        imageSaveAlpha($part_img, true);
        if ($part_img) {
            imagecopy($monster, $part_img, 0, 0, 0, 0, 120, 120);
            imagedestroy($part_img);

             // color the body
            if ($part === 'body') {
                $r = get_part(substr($hash, 0, 4), 20, 235);
                $g = get_part(substr($hash, 4, 4), 20, 235);
                $b = get_part(substr($hash, 8, 4), 20, 235);
                $color = imagecolorallocate($monster, $r, $g, $b);
                imagefill($monster, 60, 60, $color);
            }
        }
    }

    if ($size !== 120) {
        $resized = imagecreatetruecolor($size, $size);
        imagefill($resized, 0, 0, $white);
        imagecopyresampled($resized, $monster, 0, 0, 0, 0, $size, $size, 120, 120);
        imagedestroy($monster);
        return $resized;
    }

    return $monster;
}

/**
 * Converts part of the hash into an image index
 */
function get_part(string $hex, int $min, int $max): int
{
    $val = hexdec($hex);
    return ($val % ($max - $min + 1)) + $min;
}

