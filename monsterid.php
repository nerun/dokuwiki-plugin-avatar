<?php
/**
 * MonsterID Generator for DokuWiki Avatar Plugin
 *
 * Generates identicon-style monster avatars based on a seed.
 * Requires PHP GD extension.
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author  Daniel Dias Rodrigues <danieldiasr@gmail.com> (modernization)
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    $seed = preg_replace('/[^a-f0-9]/i', '', $_GET['seed'] ?? '');
    $size = (int) ($_GET['size'] ?? 120);
    $size = max(20, min(512, $size)); // limits between 20 and 512 pixels

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');

    $image = generate_monster($seed, $size);
    if ($image) {
        imagepng($image);
        imagedestroy($image);
    } else {
        http_response_code(500);
        echo 'Erro ao gerar imagem.';
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

