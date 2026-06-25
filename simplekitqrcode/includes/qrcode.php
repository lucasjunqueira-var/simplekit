<?php
/**
 * Simple Kit QRCode - QR Code Generator
 *
 * Uses psyon/php-qrcode library (MIT license) for local QR code generation.
 * Pure PHP, single file, no external API calls.
 *
 * @package SimpleKitQRCode
 * @version 1.0.0
 */
defined('ABSPATH') || exit;

require_once SIMPLEKITQRCODE_PLUGIN_DIR . 'includes/qrcode_lib.php';

/**
 * Generate QR code matrix using psyon/php-qrcode library.
 *
 * @param string $data Data to encode.
 * @return array 2D binary matrix (0=white, 1=black).
 */
function simplekitqrcode_encode($data) {
    if (!is_string($data) || $data === '') {
        return array(array(0));
    }

    try {
        // Generate QR code with M level error correction, scale=1, no padding
        $qr = new QRCode($data, array(
            's' => 'qrm',    // M level (15% error correction)
            'sf' => 1,       // scale factor = 1 (1 pixel per module)
            'p' => 0,        // no padding
        ));

        $image = $qr->render_image();
        if (!$image) {
            return array(array(1, 1), array(1, 1));
        }

        // Read pixels to build binary matrix
        $width = imagesx($image);
        $matrix = array();

        for ($y = 0; $y < $width; $y++) {
            $matrix[$y] = array();
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $rgb);
                $brightness = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;
                $matrix[$y][$x] = $brightness < 128 ? 1 : 0;
            }
        }

        imagedestroy($image);
        return $matrix;
    } catch (Exception $e) {
        return array(array(1, 1), array(1, 1));
    }
}
