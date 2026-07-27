<?php

use App\Models\Level;
use Illuminate\Support\Str;



if (!function_exists('get_setting_meta')) {
    function get_setting_meta($key)
    {
        $setting = \App\Models\Management\Setting::where('key', $key)->first();
        return $setting ? $setting->value : false;
    }
}



if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key)
    {
        $setting = \App\Models\Management\UserMeta::where('user_id', $user_id)->where('key', $key)->first();
        if ($setting) {
            if ($key === 'nip') {
                try {
                    return \Illuminate\Support\Facades\Crypt::decryptString($setting->value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    return $setting->value;
                }
            }
            return $setting->value;
        }
        return false;
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
        $nipHash = hash_hmac('sha256', $nip, config('app.key'));
        $metaHash = \App\Models\Management\UserMeta::with('user')->where('key', 'nip_hash')->where('value', $nipHash)->first();

        // Jika hash ditemukan di database, tarik item orisinil (yang key-nya nip) sebagai return
        if ($metaHash) {
            $metaNip = \App\Models\Management\UserMeta::with('user')
                ->where('user_id', $metaHash->user_id)
                ->where('key', 'nip')
                ->first();
            return $metaNip ?? $metaHash;
        }

        // --- FALLBACK UNTUK NIP YANG BELUM TER-MIGRATE ---
        $metas = \App\Models\Management\UserMeta::with('user')->where('key', 'nip')->get();
        foreach ($metas as $meta) {
            try {
                if (\Illuminate\Support\Facades\Crypt::decryptString($meta->value) === $nip) {
                    return $meta;
                }
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                if ($meta->value === $nip) {
                    return $meta;
                }
            }
        }
        return null;
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $key, $value)
    {
        if ($key === 'nip') {
            // Save the hash implementation 
            $hashValue = hash_hmac('sha256', $value, config('app.key'));
            $metaHash = \App\Models\Management\UserMeta::where('user_id', $user_id)->where('key', 'nip_hash')->first();
            if ($metaHash) {
                $metaHash->value = $hashValue;
                $metaHash->save();
            } else {
                $newMetaHash = new \App\Models\Management\UserMeta();
                $newMetaHash->user_id = $user_id;
                $newMetaHash->key = 'nip_hash';
                $newMetaHash->value = $hashValue;
                $newMetaHash->save();
            }

            $value = \Illuminate\Support\Facades\Crypt::encryptString($value);
        }

        $meta = \App\Models\Management\UserMeta::where('user_id', $user_id)->where('key', $key)->first();
        if ($meta) {
            $meta->value = $value;
            $meta->save();
        } else {
            $meta = new \App\Models\Management\UserMeta();
            $meta->user_id  = $user_id;
            $meta->key      = $key;
            $meta->value    = $value;
            $meta->save();
        }
        return $meta ?? false;
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
        return \Mews\Purifier\Facades\Purifier::clean($html);
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
