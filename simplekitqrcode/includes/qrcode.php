<?php
/**
 * Simple Kit QRCode - Local QR Code Generator
 *
 * Pure PHP QR Code encoder (ISO/IEC 18004) without external API calls.
 * Supports versions 1-10 with error correction level M (15%).
 *
 * @package SimpleKitQRCode
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

class SimpleKitQRCodeEncoder {

    // ── GF(256) tables for Reed-Solomon ──────────────────────────────
    private static $log_table = null;
    private static $exp_table = null;

    private static function init_gf_tables() {
        if (self::$log_table !== null) return;
        self::$log_table = array_fill(0, 256, 0);
        self::$exp_table = array_fill(0, 256, 0);

        $val = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp_table[$i] = $val;
            self::$log_table[$val] = $i;
            $val = $val << 1;
            if ($val & 256) {
                $val ^= 0x11D; // primitive polynomial x^8 + x^4 + x^3 + x^2 + 1
            }
        }
        self::$exp_table[255] = self::$exp_table[0]; // exp[255] = exp[0]
    }

    private static function gf_mul($a, $b) {
        if ($a === 0 || $b === 0) return 0;
        self::init_gf_tables();
        $idx = self::$log_table[$a] + self::$log_table[$b];
        if ($idx >= 255) $idx -= 255;
        return self::$exp_table[$idx];
    }

    private static function gf_pow($x, $power) {
        self::init_gf_tables();
        return self::$exp_table[(self::$log_table[$x] * $power) % 255];
    }

    // ── Reed-Solomon generator polynomial ─────────────────────────────
    private static function rs_generator_poly($degree) {
        // Start with g(x) = 1
        $g = array(1);
        for ($i = 0; $i < $degree; $i++) {
            // multiply by (x + α^i)
            $alpha = self::gf_pow(2, $i);
            $new_g = array_fill(0, count($g) + 1, 0);
            for ($j = 0; $j < count($g); $j++) {
                $new_g[$j] ^= self::gf_mul($g[$j], $alpha);
                $new_g[$j + 1] ^= $g[$j];
            }
            $g = $new_g;
        }
        return $g;
    }

    private static function rs_encode($data, $degree) {
        $gen = self::rs_generator_poly($degree);
        $len = count($data);
        $remainder = array_fill(0, $len + $degree, 0);
        for ($i = 0; $i < $len; $i++) {
            $remainder[$i] = $data[$i];
        }

        for ($i = 0; $i < $len; $i++) {
            if ($remainder[$i] !== 0) {
                $factor = self::$log_table[$remainder[$i]];
                for ($j = 0; $j < count($gen); $j++) {
                    $remainder[$i + $j] ^= self::$exp_table[($factor + self::$log_table[$gen[$j]]) % 255];
                }
            }
        }

        return array_slice($remainder, $len);
    }

    // ── Version tables ───────────────────────────────────────────────
    // Version => array(total_codewords, data_codewords, ec_codewords_per_block, blocks_group1, blocks_group2, data_per_block_g1, data_per_block_g2)
    // Error correction level M (15%)
    private static $version_info = array(
        1  => array(26,  16,  10, 1, 0, 16, 0),
        2  => array(44,  28,  16, 1, 0, 28, 0),
        3  => array(70,  44,  26, 1, 0, 44, 0),
        4  => array(100, 64,  36, 2, 0, 32, 0),
        5  => array(134, 86,  48, 2, 0, 43, 0),
        6  => array(172, 108, 64, 4, 0, 27, 0),
        7  => array(196, 124, 72, 4, 0, 31, 0),
        8  => array(242, 154, 88, 2, 2, 38, 39),
        9  => array(292, 182, 110, 3, 2, 36, 37),
        10 => array(346, 216, 130, 4, 2, 43, 44),
    );

    // Version capacity in bytes (M level)
    private static function get_version_for_data($data_len) {
        $v = 1;
        while ($v <= 10 && isset(self::$version_info[$v])) {
            $info = self::$version_info[$v];
            $total_blocks = $info[3] + $info[4];
            $max_data = $info[1] - $info[2] * $total_blocks;
            if ($data_len <= $max_data) {
                return $v;
            }
            $v++;
        }
        return 10; // max supported
    }

    // ── Alignment pattern positions ──────────────────────────────────
    private static $alignment_positions = array(
        1  => array(),
        2  => array(6, 18),
        3  => array(6, 22),
        4  => array(6, 26),
        5  => array(6, 30),
        6  => array(6, 34),
        7  => array(6, 22, 38),
        8  => array(6, 24, 42),
        9  => array(6, 26, 46),
        10 => array(6, 28, 50),
    );

    // ── Main encode method ───────────────────────────────────────────
    public static function encode($data) {
        self::init_gf_tables();

        // 1. Determine version
        $version = self::get_version_for_data(strlen($data));
        $info = self::$version_info[$version];
        $matrix_size = 17 + 4 * $version;

        // 2. Convert data to byte mode bit stream
        $bits = self::encode_byte_mode($data, $version);

        // 3. Add terminator and pad to fill data codewords
        $total_data_cw = $info[1] - $info[2] * ($info[3] + $info[4]);
        $bits = self::pad_codewords($bits, $total_data_cw);

        // 4. Split into blocks and add EC
        $blocks = self::split_into_blocks($bits, $info);
        $ec_blocks = array();
        foreach ($blocks as $block) {
            $ec_blocks[] = self::rs_encode($block, $info[2]);
        }

        // 5. Interleave data and EC blocks
        $all_data = self::interleave($blocks, $ec_blocks, $info);
        $all_ec = self::interleave_ec($blocks, $ec_blocks, $info);

        // 6. Create matrix
        $matrix = self::create_empty_matrix($matrix_size);

        // 7. Place finder patterns
        self::place_finder_pattern($matrix, 0, 0);
        self::place_finder_pattern($matrix, $matrix_size - 7, 0);
        self::place_finder_pattern($matrix, 0, $matrix_size - 7);

        // 8. Place separators
        self::place_separators($matrix, $matrix_size);

        // 9. Place timing patterns
        self::place_timing_patterns($matrix, $matrix_size);

        // 10. Place alignment patterns
        if (isset(self::$alignment_positions[$version])) {
            $positions = self::$alignment_positions[$version];
            for ($i = 0; $i < count($positions); $i++) {
                for ($j = 0; $j < count($positions); $j++) {
                    // Skip if overlapping with finder patterns
                    $row = $positions[$i];
                    $col = $positions[$j];
                    $in_finder = ($row < 9 && $col < 9) ||
                                 ($row < 9 && $col >= $matrix_size - 9) ||
                                 ($row >= $matrix_size - 9 && $col < 9);
                    if (!$in_finder) {
                        self::place_alignment_pattern($matrix, $row, $col);
                    }
                }
            }
        }

        // 11. Reserve format information areas
        self::reserve_format_areas($matrix, $matrix_size);

        // 12. Place data bits
        self::place_data_bits($matrix, array_merge($all_data, $all_ec), $matrix_size);

        // 13. Apply best mask
        $best_matrix = self::apply_best_mask($matrix, $matrix_size);

        // 14. Place format information
        self::place_format_information($best_matrix, $matrix_size, 0); // mask 0

        return $best_matrix;
    }

    // ── Byte mode encoding ──────────────────────────────────────────
    private static function encode_byte_mode($data, $version) {
        $bits = array();

        // Mode indicator: 0100 (byte mode)
        $mode_bits = array(0, 1, 0, 0);
        $bits = array_merge($bits, $mode_bits);

        // Character count (8 bits for v1-9, 16 bits for v10+)
        $len = strlen($data);
        if ($version <= 9) {
            $count_bits = str_split(str_pad(decbin($len), 8, '0', STR_PAD_LEFT));
        } else {
            $count_bits = str_split(str_pad(decbin($len), 16, '0', STR_PAD_LEFT));
        }
        $bits = array_merge($bits, array_map('intval', $count_bits));

        // Data bytes
        for ($i = 0; $i < $len; $i++) {
            $byte_val = ord($data[$i]);
            $byte_bits = str_split(str_pad(decbin($byte_val), 8, '0', STR_PAD_LEFT));
            $bits = array_merge($bits, array_map('intval', $byte_bits));
        }

        return $bits;
    }

    // ── Pad to full codewords ───────────────────────────────────────
    private static function pad_codewords($bits, $total_cw) {
        // Add terminator (0000) or as much as fits
        $data_bits = count($bits);
        $total_bits = $total_cw * 8;
        $remaining = $total_bits - $data_bits;

        if ($remaining > 0) {
            // Add terminator (up to 4 zeros)
            $term = min($remaining, 4);
            $bits = array_merge($bits, array_fill(0, $term, 0));
            $remaining -= $term;
        }

        // Pad to byte boundary
        while (count($bits) % 8 !== 0 && $remaining > 0) {
            $bits[] = 0;
            $remaining--;
        }

        // Add pad bytes: 0xEC, 0x11 alternately
        $pad_bytes = array(0xEC, 0x11);
        $pad_idx = 0;
        while (count($bits) < $total_bits) {
            $byte = $pad_bytes[$pad_idx % 2];
            $byte_bits = str_split(str_pad(decbin($byte), 8, '0', STR_PAD_LEFT));
            $bits = array_merge($bits, array_map('intval', $byte_bits));
            $pad_idx++;
        }

        return $bits;
    }

    // ── Split bits into blocks ──────────────────────────────────────
    private static function split_into_blocks($bits, $info) {
        $blocks = array();
        $pos = 0;
        $g1 = $info[3]; // number of group 1 blocks
        $g2 = $info[4]; // number of group 2 blocks
        $d1 = $info[5]; // data codewords per block in group 1
        $d2 = $info[6]; // data codewords per block in group 2

        for ($i = 0; $i < $g1; $i++) {
            $block = array();
            for ($j = 0; $j < $d1; $j++) {
                $byte = 0;
                for ($k = 0; $k < 8; $k++) {
                    $byte = ($byte << 1) | ($bits[$pos++] & 1);
                }
                $block[] = $byte;
            }
            $blocks[] = $block;
        }

        for ($i = 0; $i < $g2; $i++) {
            $block = array();
            for ($j = 0; $j < $d2; $j++) {
                $byte = 0;
                for ($k = 0; $k < 8; $k++) {
                    $byte = ($byte << 1) | ($bits[$pos++] & 1);
                }
                $block[] = $byte;
            }
            $blocks[] = $block;
        }

        return $blocks;
    }

    // ── Interleave data blocks ──────────────────────────────────────
    private static function interleave($data_blocks, $ec_blocks, $info) {
        $g1 = $info[3];
        $g2 = $info[4];
        $d1 = $info[5];
        $d2 = $info[6];
        $total_blocks = $g1 + $g2;
        $result = array();

        $max_data_len = max($d1, $d2);
        for ($i = 0; $i < $max_data_len; $i++) {
            for ($b = 0; $b < $total_blocks; $b++) {
                $block_len = ($b < $g1) ? $d1 : $d2;
                if ($i < $block_len) {
                    $result[] = $data_blocks[$b][$i];
                }
            }
        }

        return $result;
    }

    private static function interleave_ec($data_blocks, $ec_blocks, $info) {
        $g1 = $info[3];
        $g2 = $info[4];
        $total_blocks = $g1 + $g2;
        $ec_len = count($ec_blocks[0]);
        $result = array();

        for ($i = 0; $i < $ec_len; $i++) {
            for ($b = 0; $b < $total_blocks; $b++) {
                $result[] = $ec_blocks[$b][$i];
            }
        }

        return $result;
    }

    // ── Matrix operations ───────────────────────────────────────────
    private static function create_empty_matrix($size) {
        $matrix = array();
        for ($i = 0; $i < $size; $i++) {
            $matrix[$i] = array_fill(0, $size, 0);
        }
        return $matrix;
    }

    private static function place_finder_pattern(&$matrix, $row, $col) {
        // 7x7 finder pattern
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $is_outer = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                $is_inner = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                $matrix[$row + $r][$col + $c] = ($is_outer || $is_inner) ? 1 : 0;
            }
        }
    }

    private static function place_alignment_pattern(&$matrix, $row, $col) {
        // 5x5 alignment pattern
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $is_outer = ($r === -2 || $r === 2 || $c === -2 || $c === 2);
                $is_center = ($r === 0 && $c === 0);
                $matrix[$row + $r][$col + $c] = ($is_outer || $is_center) ? 1 : 0;
            }
        }
    }

    private static function place_separators(&$matrix, $size) {
        // Top-left separator
        for ($i = 0; $i < 8; $i++) {
            if ($i < 7) $matrix[7][$i] = 0; // horizontal right of top-left finder
            if ($i < 7) $matrix[$i][7] = 0; // vertical below top-left finder
        }
        $matrix[7][7] = 0;

        // Top-right separator
        for ($i = 0; $i < 8; $i++) {
            if ($i < 7) $matrix[7][$size - 1 - $i] = 0;
            if ($i < 7) $matrix[$i][$size - 8] = 0;
        }
        $matrix[7][$size - 8] = 0;

        // Bottom-left separator
        for ($i = 0; $i < 8; $i++) {
            if ($i < 7) $matrix[$size - 8][$i] = 0;
            if ($i < 7) $matrix[$size - 1 - $i][7] = 0;
        }
        $matrix[$size - 8][7] = 0;
    }

    private static function place_timing_patterns(&$matrix, $size) {
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
            $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
        }
    }

    private static function reserve_format_areas(&$matrix, $size) {
        // Dark module
        $matrix[$size - 8][8] = 1; // always dark

        // Format info areas (will be filled later with actual data)
        // Around top-left finder
        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) { // skip timing pattern row
                if ($i < 8) $matrix[8][$i] = 0; // will be set by format info
                $matrix[$i][8] = 0; // will be set by format info
            }
        }
        // Around top-right finder
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$size - 1 - $i] = 0; // will be set
        }
        // Around bottom-left finder
        for ($i = 0; $i < 8; $i++) {
            $matrix[$size - 1 - $i][8] = 0; // will be set
        }
    }

    // ── Data placement ──────────────────────────────────────────────
    private static function place_data_bits(&$matrix, $codewords, $size) {
        $bit_idx = 0;
        $total_bits = count($codewords) * 8;

        // Convert codewords to bit array
        $bits = array();
        foreach ($codewords as $cw) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($cw >> $i) & 1;
            }
        }

        // Place bits in zigzag pattern, moving upward
        $col = $size - 1;
        $row = $size - 1;
        $direction = -1; // -1 = up, 1 = down

        while ($col > 0) {
            if ($col === 6) {
                $col--; // skip timing pattern column
            }

            // Two columns at a time (right column then left column)
            for ($c = 0; $c < 2; $c++) {
                $current_col = $col - $c;

                if ($current_col < 0) break;

                if ($row < 0 || $row >= $size) {
                    // Reached edge, reverse direction and move up 2 columns
                    $direction *= -1;
                    $col -= 2;
                    if ($c === 0) {
                        $row = 0;
                        break;
                    }
                    continue 2;
                }

                // Check if this cell is available (not part of function patterns)
                if ($matrix[$row][$current_col] === 0 && !self::is_function_pattern($matrix, $row, $current_col, $size)) {
                    if ($bit_idx < count($bits)) {
                        $matrix[$row][$current_col] = $bits[$bit_idx++];
                    }
                }

                // Move to next row in current direction
                $next_row = $row + $direction;

                // If next row is out of bounds, reverse
                if ($next_row < 0 || $next_row >= $size) {
                    $direction *= -1;
                    $row = ($next_row < 0) ? 0 : $size - 1;
                    $col -= 2;
                    break;
                }

                $row = $next_row;

                // Check second column
                if ($c === 0 && ($row >= 0 && $row < $size)) {
                    if ($matrix[$row][$current_col] === 0 && !self::is_function_pattern($matrix, $row, $current_col, $size)) {
                        if ($bit_idx < count($bits)) {
                            $matrix[$row][$current_col] = $bits[$bit_idx++];
                        }
                    }
                }
            }

            // Move to next column pair
            if ($col > 0 && $col !== 6) {
                // Continue vertical movement
                $row += $direction;
                if ($row < 0 || $row >= $size) {
                    $direction *= -1;
                    $row = ($row < 0) ? 0 : $size - 1;
                    $col -= 2;
                }
            }
        }
    }

    private static function is_function_pattern($matrix, $row, $col, $size) {
        // Check if position is within any finder pattern area (including separators)
        // Top-left finder
        if ($row < 9 && $col < 9) return true;
        // Top-right finder
        if ($row < 9 && $col >= $size - 8) return true;
        // Bottom-left finder
        if ($row >= $size - 8 && $col < 9) return true;
        // Timing patterns
        if ($row === 6 || $col === 6) return true;
        // Dark module
        if ($row === $size - 8 && $col === 8) return true;

        return false;
    }

    // ── Masking ─────────────────────────────────────────────────────
    private static function apply_mask(&$matrix, $mask_pattern, $size) {
        $masked = $matrix;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (!self::is_function_pattern($matrix, $r, $c, $size) && $matrix[$r][$c] !== -1) {
                    $condition = false;
                    switch ($mask_pattern) {
                        case 0: $condition = (($r + $c) % 2 === 0); break;
                        case 1: $condition = ($r % 2 === 0); break;
                        case 2: $condition = ($c % 3 === 0); break;
                        case 3: $condition = (($r + $c) % 3 === 0); break;
                        case 4: $condition = ((int)($r / 2) + (int)($c / 3)) % 2 === 0; break;
                        case 5: $condition = (($r * $c) % 2) + (($r * $c) % 3) === 0; break;
                        case 6: $condition = ((($r * $c) % 2) + (($r * $c) % 3)) % 2 === 0; break;
                        case 7: $condition = ((($r + $c) % 2) + (($r * $c) % 3)) % 2 === 0; break;
                    }
                    if ($condition) {
                        $masked[$r][$c] = ($masked[$r][$c] === 1) ? 0 : 1;
                    }
                }
            }
        }
        return $masked;
    }

    private static function evaluate_mask($matrix, $size) {
        $penalty = 0;

        // Penalty 1: Adjacent modules in same color (5+ consecutive)
        // Rows
        for ($r = 0; $r < $size; $r++) {
            $count = 1;
            for ($c = 1; $c < $size; $c++) {
                if ($matrix[$r][$c] === $matrix[$r][$c - 1]) {
                    $count++;
                } else {
                    if ($count >= 5) $penalty += 3 + ($count - 5);
                    $count = 1;
                }
            }
            if ($count >= 5) $penalty += 3 + ($count - 5);
        }
        // Columns
        for ($c = 0; $c < $size; $c++) {
            $count = 1;
            for ($r = 1; $r < $size; $r++) {
                if ($matrix[$r][$c] === $matrix[$r - 1][$c]) {
                    $count++;
                } else {
                    if ($count >= 5) $penalty += 3 + ($count - 5);
                    $count = 1;
                }
            }
            if ($count >= 5) $penalty += 3 + ($count - 5);
        }

        // Penalty 2: 2x2 blocks of same color
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $val = $matrix[$r][$c];
                if ($matrix[$r][$c + 1] === $val &&
                    $matrix[$r + 1][$c] === $val &&
                    $matrix[$r + 1][$c + 1] === $val) {
                    $penalty += 3;
                }
            }
        }

        // Penalty 3: Finder-like patterns (10111010000 or 00001011101)
        $pattern1 = array(1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0);
        $pattern2 = array(0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1);
        $p_len = count($pattern1);

        // Rows
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c <= $size - $p_len; $c++) {
                $match1 = true;
                $match2 = true;
                for ($k = 0; $k < $p_len; $k++) {
                    if ($matrix[$r][$c + $k] !== $pattern1[$k]) $match1 = false;
                    if ($matrix[$r][$c + $k] !== $pattern2[$k]) $match2 = false;
                }
                if ($match1 || $match2) $penalty += 40;
            }
        }
        // Columns
        for ($c = 0; $c < $size; $c++) {
            for ($r = 0; $r <= $size - $p_len; $r++) {
                $match1 = true;
                $match2 = true;
                for ($k = 0; $k < $p_len; $k++) {
                    if ($matrix[$r + $k][$c] !== $pattern1[$k]) $match1 = false;
                    if ($matrix[$r + $k][$c] !== $pattern2[$k]) $match2 = false;
                }
                if ($match1 || $match2) $penalty += 40;
            }
        }

        // Penalty 4: Proportion of dark modules (simplified)
        $dark = 0;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === 1) $dark++;
            }
        }
        $total = $size * $size;
        $percent = ($dark * 100) / $total;
        $prev = (int)($percent / 5) * 5;
        $next = $prev + 5;
        $penalty += min(abs($prev - 50) / 5 * 10, abs($next - 50) / 5 * 10);

        return (int)$penalty;
    }

    private static function apply_best_mask($matrix, $size) {
        $best_score = PHP_INT_MAX;
        $best_matrix = null;

        for ($mask = 0; $mask < 8; $mask++) {
            $masked = self::apply_mask($matrix, $mask, $size);
            // Temporarily place format info for evaluation
            $temp = self::place_format_info($masked, $size, $mask);
            $score = self::evaluate_mask($temp, $size);

            if ($score < $best_score) {
                $best_score = $score;
                $best_matrix = $masked;
            }
        }

        return $best_matrix;
    }

    // ── Format information ──────────────────────────────────────────
    private static function calculate_bch_format($data) {
        // Generator polynomial: x^10 + x^8 + x^5 + x^4 + x^2 + x + 1 (0x537)
        $g = 0b10100110111;
        $d = $data << 10;
        for ($i = 14; $i >= 10; $i--) {
            if (($d >> $i) & 1) {
                $d ^= $g << ($i - 10);
            }
        }
        return ($data << 10) | $d;
    }

    private static function place_format_info(&$matrix, $size, $mask) {
        // EC level M = 00, so format data = (00 << 13) | (mask << 10) | BCH
        $format_data = self::calculate_bch_format($mask);
        // XOR with mask 101010000010010
        $format_data ^= 0b101010000010010;

        $result = $matrix;

        // Place in top-left area
        $positions_tl = array(
            array(0,8), array(1,8), array(2,8), array(3,8), array(4,8), array(5,8),
            array(7,8), array(8,8),
            array(8,7), array(8,5), array(8,4), array(8,3), array(8,2), array(8,1), array(8,0)
        );

        // Place in top-right area
        $positions_tr = array(
            array(8, $size-1), array(8, $size-2), array(8, $size-3), array(8, $size-4),
            array(8, $size-5), array(8, $size-6), array(8, $size-7)
        );

        // Place in bottom-left area
        $positions_bl = array(
            array($size-1, 8), array($size-2, 8), array($size-3, 8), array($size-4, 8),
            array($size-5, 8), array($size-6, 8), array($size-7, 8)
        );

        // All positions in order
        $all_positions = array_merge($positions_tl, $positions_tr, $positions_bl);

        for ($i = 0; $i < 15 && $i < count($all_positions); $i++) {
            $bit = ($format_data >> (14 - $i)) & 1;
            $r = $all_positions[$i][0];
            $c = $all_positions[$i][1];
            if ($r >= 0 && $r < $size && $c >= 0 && $c < $size) {
                // Don't overwrite timing pattern at (8,6) which is timing
                if ($c !== 6 && $r !== 6 && $r !== 8 && $c !== 8) {
                    // Actually, we need to handle this properly
                }
                // Skip timing pattern cells
                if ($c === 6 && $r === 8) continue; // skip timing
                if ($r === 6 && $c === 8) continue; // skip timing
                $result[$r][$c] = $bit;
            }
        }

        // Dark module
        $result[$size - 8][8] = 1;

        return $result;
    }

    private static function place_format_information(&$matrix, $size, $mask) {
        // EC level M = 00
        $format_data = self::calculate_bch_format($mask);
        $format_data ^= 0b101010000010010;

        // Top-left: row 8, columns 0-8 (skip column 6 which is timing)
        $fmt_bits = array();
        for ($i = 14; $i >= 0; $i--) {
            $fmt_bits[] = ($format_data >> $i) & 1;
        }

        // Place in row 8 (columns 0-8, skip 6)
        $col_idx = 0;
        for ($c = 0; $c < 9; $c++) {
            if ($c === 6) continue; // skip timing
            if ($col_idx < 15) {
                $matrix[8][$c] = $fmt_bits[$col_idx++];
            }
        }

        // Place in column 8 (rows 0-8, skip 6)
        for ($r = 0; $r < 8; $r++) {
            if ($r === 6) continue; // skip timing
            if ($col_idx < 15) {
                $matrix[$r][8] = $fmt_bits[$col_idx++];
            }
        }

        // Top-right: row 8, columns size-8 to size-1
        $col_idx = 7; // continue from 7 (bits 7-0)
        for ($c = $size - 8; $c < $size; $c++) {
            if ($col_idx < 15) {
                $matrix[8][$c] = $fmt_bits[$col_idx++];
            }
        }

        // Bottom-left: rows size-8 to size-1, column 8
        $col_idx = 0;
        for ($r = $size - 8; $r < $size; $r++) {
            if ($r === $size - 8) continue; // skip dark module row - actually this is the dark module row
            // Actually, top-right uses bits 7-0, bottom-left uses bits 0-6 and 8-14
        }

        // Let me simplify this - standard QR format info placement
        // Clear the format areas first
        // Top-left horizontal
        for ($c = 0; $c < 9; $c++) {
            if ($c !== 6) {
                // skip, will be set below
            }
        }

        // Actually, let me use the standard approach
        $positions = array();
        // Horizontal at top-left (row 8, cols 0-8, skip 6)
        for ($c = 0; $c <= 8; $c++) {
            if ($c !== 6) $positions[] = array(8, $c);
        }
        // Vertical at top-left (rows 0-8, col 8, skip 6)
        for ($r = 7; $r >= 0; $r--) {
            if ($r !== 6) $positions[] = array($r, 8);
        }
        // Horizontal at top-right (row 8, cols size-8 to size-1)
        for ($c = $size - 8; $c < $size; $c++) {
            $positions[] = array(8, $c);
        }
        // Vertical at bottom-left (rows size-8 to size-1, col 8)
        for ($r = $size - 1; $r >= $size - 7; $r--) {
            $positions[] = array($r, 8);
        }

        for ($i = 0; $i < 15 && $i < count($positions); $i++) {
            $r = $positions[$i][0];
            $c = $positions[$i][1];
            if ($r >= 0 && $r < $size && $c >= 0 && $c < $size) {
                $matrix[$r][$c] = $fmt_bits[$i];
            }
        }

        // Dark module
        $matrix[$size - 8][8] = 1;
    }
}
