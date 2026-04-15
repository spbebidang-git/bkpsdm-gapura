<?php

use App\Models\Level;

if (!function_exists('generate_year_month')) {
    function generate_year_month($month1, $month2)
    {
        $yearConvert1 = floor($month1 / 12);
        $monthConvert1 = $month1 % 12;

        $yearConvert2 = floor($month2 / 12);
        $monthConvert2 = $month2 % 12;

        $result1 = $monthConvert1 > 0 ? $yearConvert1 . ' Tahun ' . $monthConvert1 . ' Bulan ' : $yearConvert1 . ' Tahun';
        $result2 = $monthConvert2 > 0 ? $yearConvert2 . ' Tahun ' . $monthConvert2 . ' Bulan ' : $yearConvert2 . ' Tahun';

        return $result1 . ' - ' . $result2;
    }
}

if (!function_exists('get_setting_meta')) {
    function get_setting_meta($key)
    {
        $setting = \App\Models\Management\Setting::where('key', $key)->first();
        return $setting ? $setting->value : false;
    }
}

if (!function_exists('update_setting_meta')) {
    function update_setting_meta($key, $value)
    {
        $meta = \App\Models\Management\Setting::where('key', $key)->first();
        if ($meta) {
            $meta->value = $value;
            $meta->save();
        } else {
            $meta = new \App\Models\Management\Setting();
            $meta->key      = $key;
            $meta->value    = $value;
            $meta->save();
        }
        return $meta ?? false;
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

if (!function_exists('find_user_by_nip')) {
    function find_user_by_nip($nip)
    {
        $nipHash = hash('sha256', $nip);
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
            $hashValue = hash('sha256', $value);
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

if (!function_exists('remove_user_meta')) {
    function remove_user_meta($user_id, $key)
    {
        $meta = \App\Models\Management\UserMeta::where('user_id', $user_id)->where('key', $key)->first();

        if ($meta) {
            $deleted_id = $meta->delete();

            return $deleted_id;
        }

        return false;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key)
    {
        $post = \App\Models\PostMeta::where(['post_id' => $post_id, 'key' => $key])->first();
        return $post ? $post->value : false;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value)
    {
        $meta = \App\Models\PostMeta::where('post_id', $post_id)->where('key', $key)->first();
        if ($meta) {
            $meta->value = $value;
            $meta->save();
        } else {
            $meta = new \App\Models\PostMeta();
            $meta->post_id  = $post_id;
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

if (!function_exists('formatIdr')) {
    function formatIdr($value)
    {
        return 'Rp' . number_format($value, 0, ",", ".");
    }
}

if (!function_exists('isRegisteredCourse')) {
    function isRegisteredCourse($user_id, $course_id)
    {
        $learn = new App\Models\Learn();
        $learn->where(['user_id' => $user_id, 'course_id' => $course_id])->first();

        dd();
        if ($learn) {
            return true;
        }

        return false;
    }
}

if (!function_exists('getScoreCustomer')) {
    function getScoreCustomer($questionId, $question, $answer)
    {
        $questionItems = App\Models\QuestionItem::with('questionOptions')->findOrFail($questionId);

        $correctAnswer = $questionItems->questionOptions->where('is_correct', 1)->pluck('option')->first();
        if ($correctAnswer == $answer) {
            return $questionItems->score;
        }


        return 0;
        // $correctAnswer = $questionItems->where()

        // foreach($allOption as $item){
        //     if($item ==)
        // }

        // // dd($allOption);

        // $correctAnswer = search($allOption, 'is_correct', true);
        // if ($correctAnswer[0]['option'] == $answer) {
        //     return $questionItems->score;
        // }

        // return 0;

        // return $allOption;
        // return $correctAnswer;
        // dd($correctAnswer);
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

if (!function_exists('feedbackLearn')) {
    function feedbackLearn()
    {
        return true;
    }
}

if (!function_exists('getTextValue')) {
    function getTextValue($levelId, $value)
    {
        // $level = Level::with('levelItems')->where('id', $levelId)->whereRelation('levelItems', 'value', '=', $value)->first();

        // dd($levelId, $value);
        // return $level->levelItems->first()->text_level;
        $result = DB::table('levels')
            ->select('level_items.text_level')
            ->join('level_items', 'level_items.level_id', '=', 'levels.id')
            ->where('level_items.level_id', $levelId)
            ->where('level_items.value', $value)
            ->groupBy('level_items.level_id')
            ->first();

        if (!$result) {
            return '-';
        }
        return $result->text_level;
    }
}

if (!function_exists('getVariantNumber')) {
    function getVariantNumber($questionId)
    {
        $questionVariant = QuestionVariant::where('question_id', $questionId)->orderBy('variant', 'DESC')->pluck('variant')->first();

        if (!$questionVariant) {
            return 1;
        }
        if ($questionVariant == 10) {
            return '-';
        }
        return $questionVariant + 1;
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

if (!function_exists('getStatusLearn')) {
    function getStatusLearn($learnId, $activityId)
    {
        $learn = LearnItem::where([
            'learn_id' => $learnId,
            'activity_id' => $activityId
        ])->first();


        if ($learn) {
            if ($learn->status === 'in_progress') {
                return '<span class="text-yellow-500">Berlangsung</span>';
            }

            if ($learn->status === 'done') {
                return '<span class="text-custom-green">Selesai</span>';
            }
        }

        return '<span class="text-primary">Tersedia</span>';
    }
}

if (!function_exists('formatIdr')) {
    function formatIdr($value)
    {
        return 'Rp' . number_format($value, 0, ",", ".");
    }
}

if (!function_exists('getRecommendation')) {
    function getRecommendation($answer)
    {
        $score = 0;
        foreach ($answer as $item) {
            $score += $item->answer->score;
        };

        $recommendation = App\Models\Recommendation::whereRaw('? between score_from and score_to', $score)->first();
        $data = [
            'score' => $score,
            'recommendation' => $recommendation
        ];

        return $data;
    }
}

if (!function_exists('getScoreIndicator')) {
    function getScoreIndicator($answer)
    {
        $score = 0;
        foreach ($answer as $item) {
            $score += $item->answer->score;
        };

        return $score;
    }
}


if (!function_exists('getFavourablePercent')) {
    function getFavourablePercent($driverId)
    {

        $result = DB::table(function ($query) use ($driverId) {
            $query->select(
                'drivers.id as driver_id',
                'participant_answer_items.value as value',
                DB::raw('COUNT(participant_answer_items.value) as total'),
                DB::raw('ROUND((COUNT(participant_answer_items.value) / sub.sum_ptc) * 100, 2) as percentage')
            )
                ->from('participant_answers')
                ->join('participant_answer_items', 'participant_answer_items.participant_answer_id', '=', 'participant_answers.id')
                ->join('users', 'users.id', '=', 'participant_answers.participant_id')
                ->join('user_metas', 'user_metas.user_id', '=', 'users.id')
                ->join('questioners', 'questioners.id', '=', 'participant_answer_items.question_id')
                ->join('drivers', 'drivers.id', '=', 'questioners.driver_id')
                ->join('levels', 'levels.id', '=', 'questioners.level_id')
                ->join('dimensions', 'dimensions.id', '=', 'questioners.dimension_id')
                ->joinSub(function ($subquery) use ($driverId) {
                    $subquery->select(
                        'drivers.id as driver_id',
                        DB::raw('COUNT(DISTINCT users.id) as sum_ptc')
                    )
                        ->from('participant_answers')
                        ->join('participant_answer_items', 'participant_answer_items.participant_answer_id', '=', 'participant_answers.id')
                        ->join('users', 'users.id', '=', 'participant_answers.participant_id')
                        ->join('user_metas', 'user_metas.user_id', '=', 'users.id')
                        ->join('questioners', 'questioners.id', '=', 'participant_answer_items.question_id')
                        ->join('drivers', 'drivers.id', '=', 'questioners.driver_id')
                        ->join('levels', 'levels.id', '=', 'questioners.level_id')
                        ->join('dimensions', 'dimensions.id', '=', 'questioners.dimension_id')
                        ->where('user_metas.key', 'client_id')
                        ->where('user_metas.value', 10)
                        ->where('questioners.type', 'likert_scale')
                        ->where('drivers.id', $driverId)
                        ->groupBy('drivers.id');
                }, 'sub', function ($join) {
                    $join->on('sub.driver_id', '=', 'drivers.id');
                })
                ->where('user_metas.key', 'client_id')
                ->where('user_metas.value', 10)
                ->where('questioners.type', 'likert_scale')
                ->where('drivers.id', $driverId)
                ->where('participant_answer_items.value', '>', 3)
                ->groupBy('drivers.id', 'participant_answer_items.value');
        })->select('driver_id', DB::raw('ROUND(SUM(percentage)) as percentage_sum'))
            ->groupBy('driver_id')
            ->first();

        // dd($result);
        return $result->percentage_sum;
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
