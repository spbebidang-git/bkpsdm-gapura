<?php

use Illuminate\Support\Str;



if (!function_exists('get_setting_meta')) {
    function get_setting_meta($key)
    {
        return app(\App\Services\System\SettingService::class)->get((string) $key);
    }
}

if (!function_exists('update_setting_meta')) {
    function update_setting_meta($key, $value)
    {
        return app(\App\Services\System\SettingService::class)->put((string) $key, $value);
    }
}



if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key)
    {
        return app(\App\Services\Identity\UserMetaService::class)->get((string) $user_id, (string) $key);
    }
}

/**
 * Bersihkan NIP untuk payload SSO: digit only, unicode lookalike → ASCII.
 * Panjang bebas (min 1) — format NIP bervariasi, jangan hardcode 9/16/18.
 */
if (!function_exists('normalize_nip')) {
    function normalize_nip($nip): ?string
    {
        if ($nip === false || $nip === null || $nip === '') {
            return null;
        }

        $s = trim((string) $nip);
        $s = strtr($s, [
            '⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4',
            '⁵' => '5', '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9',
            '₀' => '0', '₁' => '1', '₂' => '2', '₃' => '3', '₄' => '4',
            '₅' => '5', '₆' => '6', '₇' => '7', '₈' => '8', '₉' => '9',
        ]);
        $s = preg_replace('/\D+/', '', $s) ?? '';

        return $s !== '' ? $s : null;
    }
}

if (!function_exists('find_user_by_nip')) {
    function find_user_by_nip($nip)
    {
        return app(\App\Services\Identity\UserMetaService::class)->findUserByNip((string) $nip);
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $key, $value)
    {
        return app(\App\Services\Identity\UserMetaService::class)->put((string) $user_id, (string) $key, (string) $value);
    }
}



if (!function_exists('random_filename')) {
    function random_filename($file)
    {
        return generate_unique_string() . '.' . $file->getClientOriginalExtension();
    }
}

if (!function_exists('generate_unique_string')) {
    function generate_unique_string($length = null, $start_string = null, $finish_string = null)
    {
        $string = '';
        if ($start_string) {
            $string .= $start_string;
        }
        if ($length) {
            $string .= str_shuffle(strtoupper(Str::random($length) . rand(1, 100)));
        } else {
            $string .= str_shuffle(strtoupper(Str::random() . rand(1, 100)));
        }
        if ($finish_string) {
            $string .= $finish_string;
        }

        return $string;
    }
}

if (!function_exists('is_file_exists')) {
    function is_file_exists($file)
    {
        return $file !== null and is_file($file);
    }
}

if (!function_exists('remove_file')) {
    function remove_file($path)
    {
        if (unlink($path)) {
            return true;
        }

        return false;
    }
}





if (!function_exists('search')) {
    function search($array, $key, $value)
    {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, search($subarray, $key, $value));
            }
        }

        return $results;
    }
}



if (!function_exists('getTotalScore')) {
    function getTotalScore($response)
    {
        // dd($response);
        $a = 0;
        foreach ($response as $item) {
            $a = $a + $item['score'];
        }

        return $a;
    }
}

if (!function_exists('getCorrectAnswer')) {
    function getCorrectAnswer($response)
    {
        $a = search($response, 'is_correct', true);
        return count($a);
    }
}

if (!function_exists('getWrongAnswer')) {
    function getWrongAnswer($response)
    {
        $a = search($response, 'is_correct', false);
        return count($a);
    }
}





if (!function_exists('sanitize_html')) {
    /**
     * Sanitize HTML content (for WYSIWYG/rich text fields).
     * Allows safe HTML tags, removes dangerous scripts/events.
     */
    function sanitize_html($html)
    {
        if (empty($html)) {
            return $html;
        }
        return (new \voku\helper\AntiXSS())->xss_clean($html);
    }
}

if (!function_exists('sanitize_plain_text')) {
    /**
     * Strip all HTML tags from input (for plain text fields like textarea).
     */
    function sanitize_plain_text($text)
    {
        if (empty($text)) {
            return $text;
        }
        return strip_tags($text);
    }
}
