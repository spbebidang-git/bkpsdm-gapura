<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Activity;
use App\Models\Course\Questionnaire;
use App\Models\Course\Questionnaire\QuestionnaireAnswer;
use App\Models\Course\Questionnaire\QuestionnaireChoice;
use App\Models\Course\Questionnaire\QuestionnaireQuestion;
use App\Models\Course\Questionnaire\QuestionnaireResponse;
use App\Services\Learn\LearnService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Kuesioner/survei yang diisi peserta langsung di aplikasi.
 *
 * Tipe pertanyaannya mengikuti mod_questionnaire Moodle (yesno, text, essay,
 * radio, check, drop, rate, date, numeric + section sebagai teks pengantar)
 * supaya kuesioner dari backup .mbz bisa dipindahkan apa adanya. Berbeda dari
 * pre-test/post-test, jawaban kuesioner TIDAK dinilai — yang dibutuhkan
 * instruktur adalah rekapnya.
 */
class QuestionnaireService
{
    /** Batas aman panjang isian singkat bila paket asal tidak menyebutkannya. */
    const DEFAULT_TEXT_LIMIT = 255;

    protected LearnService $learn;

    public function __construct(LearnService $learnService)
    {
        $this->learn = $learnService;
    }

    /* ----------------------------------------------------------------------
     | Instruktur: buat & ubah
     * -------------------------------------------------------------------- */

    /**
     * @param  array  $data  activity_id, sub_title, intro, is_anonymous,
     *                       duration, questions[]
     */
    public function createQuestionnaire(array $data): Questionnaire
    {
        $questionnaire = Questionnaire::create([
            'activity_id'  => $data['activity_id'],
            'sub_title'    => $data['sub_title'] ?? null,
            'intro'        => sanitize_html($data['intro'] ?? null),
            'is_anonymous' => !empty($data['is_anonymous']),
            'duration'     => (int) ($data['duration'] ?? 0),
        ]);

        $this->syncQuestions($questionnaire, $data['questions'] ?? []);

        return $questionnaire;
    }

    public function updateQuestionnaire(array $data): bool
    {
        $questionnaire = Questionnaire::where('activity_id', $data['activity_id'])->firstOrFail();

        $questionnaire->update([
            'sub_title'    => $data['sub_title'] ?? null,
            'intro'        => sanitize_html($data['intro'] ?? null),
            'is_anonymous' => !empty($data['is_anonymous']),
            'duration'     => (int) ($data['duration'] ?? 0),
        ]);

        $this->syncQuestions($questionnaire, $data['questions'] ?? []);

        return true;
    }

    /**
     * Tulis ulang daftar pertanyaan. Pertanyaan yang id-nya masih dikirim akan
     * diperbarui sehingga jawaban yang sudah masuk tetap menempel; sisanya
     * dihapus (beserta jawabannya, lewat cascade).
     *
     * @param  array  $questions  [['id'=>?, 'type'=>.., 'content'=>.., 'is_required'=>bool, 'length'=>?, 'choices'=>[string]]]
     */
    public function syncQuestions(Questionnaire $questionnaire, array $questions): void
    {
        $keptIds = [];
        $position = 0;

        foreach ($questions as $row) {
            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $type = $this->normalizeType($row['type'] ?? 'text');
            $position += 10;

            $attributes = [
                'type'        => $type,
                // Dirender sebagai HTML di form peserta (teks Moodle bisa
                // mengandung penekanan), jadi dibersihkan lebih dulu.
                'content'     => sanitize_html($content),
                'is_required' => $type === 'section' ? false : !empty($row['is_required']),
                'position'    => $position,
                'length'      => (int) ($row['length'] ?? 0),
            ];

            $existing = !empty($row['id'])
                ? QuestionnaireQuestion::where('questionnaire_id', $questionnaire->id)
                    ->where('id', $row['id'])
                    ->first()
                : null;

            if ($existing) {
                $existing->update($attributes);
                $question = $existing;
            } else {
                $question = QuestionnaireQuestion::create(
                    $attributes + ['questionnaire_id' => $questionnaire->id]
                );
            }

            $keptIds[] = $question->id;
            $this->syncChoices($question, $row['choices'] ?? []);
        }

        QuestionnaireQuestion::where('questionnaire_id', $questionnaire->id)
            ->whereNotIn('id', $keptIds ?: [0])
            ->delete();
    }

    /** @param  array  $choices  daftar teks pilihan (string). */
    protected function syncChoices(QuestionnaireQuestion $question, array $choices): void
    {
        if (!in_array($question->type, QuestionnaireQuestion::CHOICE_TYPES, true)) {
            QuestionnaireChoice::where('question_id', $question->id)->delete();
            return;
        }

        $clean = [];
        foreach ($choices as $choice) {
            $text = trim((string) (is_array($choice) ? ($choice['content'] ?? '') : $choice));
            if ($text !== '') {
                $clean[] = Str::limit($text, 495, '');
            }
        }

        $existing = QuestionnaireChoice::where('question_id', $question->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values();

        // Pilihan dicocokkan berdasarkan urutan: baris yang sudah ada dipakai
        // ulang supaya jawaban yang menunjuk ke pilihan itu tidak hilang.
        foreach ($clean as $index => $text) {
            $position = ($index + 1) * 10;

            if ($row = $existing->get($index)) {
                $row->update(['content' => $text, 'position' => $position]);
                continue;
            }

            QuestionnaireChoice::create([
                'question_id' => $question->id,
                'content'     => $text,
                'position'    => $position,
            ]);
        }

        $surplus = $existing->slice(count($clean));
        if ($surplus->isNotEmpty()) {
            QuestionnaireChoice::whereIn('id', $surplus->pluck('id'))->delete();
        }
    }

    /**
     * Buat kuesioner dari hasil parse .mbz (MbzParser::resolveQuestionnaire()).
     *
     * @param  array  $data  activity_id, title, intro, form_questions[]
     */
    public function createFromMbz(array $data): Questionnaire
    {
        $questions = [];

        foreach ($data['form_questions'] ?? [] as $row) {
            $questions[] = [
                'type'        => !empty($row['is_section']) ? 'section' : ($row['type_key'] ?? 'text'),
                'content'     => $row['text'] ?? '',
                'is_required' => $row['required'] ?? false,
                'length'      => $row['length'] ?? 0,
                'choices'     => $row['choices'] ?? [],
            ];
        }

        return $this->createQuestionnaire([
            'activity_id'  => $data['activity_id'],
            'sub_title'    => Str::limit($data['title'] ?? '', 250, ''),
            'intro'        => $data['intro'] ?? null,
            // Mengikuti <respondenttype> Moodle; instruktur masih bisa
            // mengubahnya lewat halaman Edit.
            'is_anonymous' => !empty($data['is_anonymous']),
            'duration'     => 0,
            'questions'    => $questions,
        ]);
    }

    /* ----------------------------------------------------------------------
     | Penyajian
     * -------------------------------------------------------------------- */

    /** Payload untuk komponen Activity (peserta maupun pratinjau instruktur). */
    public function getQuestionnaire($activity, $participantId = null): array
    {
        $questionnaire = Questionnaire::with('questions.choices')
            ->where('activity_id', $activity->id)
            ->first();

        if (!$questionnaire) {
            return [
                'activity_id' => $activity->id,
                'title'       => $activity->title,
                'type'        => 'questionnaire',
                'data'        => [
                    'sub_title'    => null,
                    'intro'        => null,
                    'duration'     => 0,
                    'is_anonymous' => false,
                    'questions'    => [],
                    'answers'      => [],
                    'is_answered'  => false,
                ],
            ];
        }

        $response = $participantId
            ? QuestionnaireResponse::with('answers')
                ->where('questionnaire_id', $questionnaire->id)
                ->where('participant_id', $participantId)
                ->first()
            : null;

        return [
            'activity_id' => $activity->id,
            'title'       => $activity->title,
            'type'        => 'questionnaire',
            'data'        => [
                'sub_title'    => $questionnaire->sub_title,
                'intro'        => $questionnaire->intro,
                'duration'     => $questionnaire->duration,
                'is_anonymous' => (bool) $questionnaire->is_anonymous,
                'questions'    => $questionnaire->questions->map(fn($q) => $this->presentQuestion($q))->values(),
                'answers'      => $response ? $this->presentAnswers($questionnaire, $response) : [],
                'is_answered'  => (bool) $response,
            ],
        ];
    }

    protected function presentQuestion(QuestionnaireQuestion $question): array
    {
        return [
            'id'          => $question->id,
            'type'        => $question->type,
            'type_label'  => QuestionnaireQuestion::TYPES[$question->type] ?? $question->type,
            'content'     => $question->content,
            'is_required' => (bool) $question->is_required,
            'length'      => (int) $question->length,
            'choices'     => $question->choices->map(fn($c) => [
                'id'      => $c->id,
                'content' => $c->content,
            ])->values(),
        ];
    }

    /**
     * Jawaban yang sudah tersimpan, dalam bentuk yang langsung dipakai form:
     * question_id => ['value'=>..,'choice_id'=>..,'choice_ids'=>[..],'rates'=>[choice_id=>nilai]]
     */
    protected function presentAnswers(Questionnaire $questionnaire, QuestionnaireResponse $response): array
    {
        $byQuestion = $response->answers->groupBy('question_id');
        $answers = [];

        foreach ($questionnaire->questions as $question) {
            $rows = $byQuestion->get($question->id);
            if (!$rows) {
                continue;
            }

            switch ($question->type) {
                case 'check':
                    $answers[$question->id] = [
                        'choice_ids' => $rows->pluck('choice_id')->filter()->values(),
                    ];
                    break;

                case 'radio':
                case 'drop':
                    $answers[$question->id] = ['choice_id' => $rows->first()->choice_id];
                    break;

                case 'rate':
                    $answers[$question->id] = [
                        'rates' => $rows->filter(fn($r) => $r->choice_id)
                            ->mapWithKeys(fn($r) => [$r->choice_id => $r->value])
                            ->all(),
                    ];
                    break;

                default:
                    $answers[$question->id] = ['value' => $rows->first()->value];
            }
        }

        return $answers;
    }

    public function getQuestionnaireForEdit($activity): array
    {
        $questionnaire = Questionnaire::with('questions.choices')
            ->where('activity_id', $activity->id)
            ->first();

        return [
            'id'             => $activity->id,
            'title'          => $activity->title,
            'value'          => $activity->value,
            'instruction'    => $activity->instruction,
            'sub_title'      => $questionnaire->sub_title ?? null,
            'intro'          => $questionnaire->intro ?? null,
            'is_anonymous'   => (bool) ($questionnaire->is_anonymous ?? false),
            'duration'       => $questionnaire->duration ?? 0,
            'total_response' => $questionnaire
                ? QuestionnaireResponse::where('questionnaire_id', $questionnaire->id)->count()
                : 0,
            'questions'      => $questionnaire
                ? $questionnaire->questions->map(fn($q) => [
                    'id'          => $q->id,
                    'type'        => $q->type,
                    'content'     => $q->content,
                    'is_required' => (bool) $q->is_required,
                    'length'      => (int) $q->length,
                    'choices'     => $q->choices->pluck('content')->values(),
                ])->values()
                : [],
        ];
    }

    /* ----------------------------------------------------------------------
     | Peserta: simpan jawaban
     * -------------------------------------------------------------------- */

    public function storeQuestionnaire($participantId, $courseId, $activityId, $request)
    {
        $questionnaire = Questionnaire::with('questions.choices')
            ->where('activity_id', $activityId)
            ->firstOrFail();

        $payload = $request['answers'] ?? [];
        $payload = is_array($payload) ? $payload : [];

        $this->validateAnswers($questionnaire, $payload);

        $response = QuestionnaireResponse::updateOrCreate(
            [
                'questionnaire_id' => $questionnaire->id,
                'participant_id'   => $participantId,
            ],
            ['submitted_at' => Carbon::now()]
        );

        // Pengisian ulang menggantikan jawaban sebelumnya seutuhnya.
        QuestionnaireAnswer::where('response_id', $response->id)->delete();

        foreach ($questionnaire->questions as $question) {
            foreach ($this->answerRows($question, $payload[$question->id] ?? null) as $row) {
                QuestionnaireAnswer::create($row + [
                    'response_id' => $response->id,
                    'question_id' => $question->id,
                ]);
            }
        }

        $this->learn->createLearn($participantId, $courseId, $activityId, null, [
            'type'     => 'questionnaire',
            'status'   => 'done',
            'response' => null,
        ]);

        return true;
    }

    /**
     * Ubah satu jawaban menjadi baris-baris questionnaire_answers.
     *
     * @return array<int, array{choice_id: ?int, value: ?string}>
     */
    protected function answerRows(QuestionnaireQuestion $question, $answer): array
    {
        if ($question->type === 'section') {
            return [];
        }

        $answer = is_array($answer) ? $answer : ['value' => $answer];
        $validChoiceIds = $question->choices->pluck('id')->all();

        switch ($question->type) {
            case 'check':
                $rows = [];
                foreach ((array) ($answer['choice_ids'] ?? []) as $choiceId) {
                    if (in_array((int) $choiceId, $validChoiceIds, true)) {
                        $rows[] = ['choice_id' => (int) $choiceId, 'value' => null];
                    }
                }
                return $rows;

            case 'radio':
            case 'drop':
                $choiceId = (int) ($answer['choice_id'] ?? 0);
                return in_array($choiceId, $validChoiceIds, true)
                    ? [['choice_id' => $choiceId, 'value' => null]]
                    : [];

            case 'rate':
                $rows = [];
                foreach ((array) ($answer['rates'] ?? []) as $choiceId => $value) {
                    if (in_array((int) $choiceId, $validChoiceIds, true) && $value !== null && $value !== '') {
                        $rows[] = ['choice_id' => (int) $choiceId, 'value' => (string) (int) $value];
                    }
                }
                return $rows;

            case 'yesno':
                $value = strtolower(trim((string) ($answer['value'] ?? '')));
                return in_array($value, ['y', 'n'], true)
                    ? [['choice_id' => null, 'value' => $value]]
                    : [];

            default:
                $value = trim((string) ($answer['value'] ?? ''));
                return $value === '' ? [] : [['choice_id' => null, 'value' => $value]];
        }
    }

    /**
     * Pertanyaan wajib harus terisi dan isian bebas tidak boleh melewati batas
     * panjangnya. Kesalahan dikembalikan dengan kunci `answers.<question_id>`
     * agar form bisa menandainya per pertanyaan.
     */
    protected function validateAnswers(Questionnaire $questionnaire, array $payload): void
    {
        $errors = [];

        foreach ($questionnaire->questions as $question) {
            if ($question->type === 'section') {
                continue;
            }

            $rows = $this->answerRows($question, $payload[$question->id] ?? null);
            $key = "answers.{$question->id}";

            if (empty($rows)) {
                if ($question->is_required) {
                    $errors[$key] = 'Pertanyaan ini wajib dijawab.';
                }
                continue;
            }

            if ($question->type === 'rate') {
                if ($question->is_required && count($rows) < $question->choices->count()) {
                    $errors[$key] = 'Semua baris pada pertanyaan ini wajib dinilai.';
                }
                continue;
            }

            if (in_array($question->type, ['text', 'essay'], true)) {
                $limit = $question->length > 0
                    ? $question->length
                    : ($question->type === 'text' ? self::DEFAULT_TEXT_LIMIT : 0);

                if ($limit > 0 && mb_strlen($rows[0]['value']) > $limit) {
                    $errors[$key] = "Jawaban maksimal {$limit} karakter.";
                }
                continue;
            }

            if ($question->type === 'numeric') {
                if (!is_numeric($rows[0]['value'])) {
                    $errors[$key] = 'Jawaban harus berupa angka.';
                    continue;
                }

                $digits = strlen(preg_replace('/\D/', '', $rows[0]['value']));
                if ($question->length > 0 && $digits > $question->length) {
                    $errors[$key] = "Jawaban maksimal {$question->length} digit.";
                }

                continue;
            }

            if ($question->type === 'date' && !strtotime($rows[0]['value'])) {
                $errors[$key] = 'Tanggal tidak valid.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /* ----------------------------------------------------------------------
     | Instruktur: rekap
     * -------------------------------------------------------------------- */

    /** Rekap per pertanyaan + daftar responden (kosong bila anonim). */
    public function getRecap(Activity $activity): array
    {
        $questionnaire = Questionnaire::with('questions.choices')
            ->where('activity_id', $activity->id)
            ->first();

        if (!$questionnaire) {
            return [
                'sub_title'      => null,
                'is_anonymous'   => false,
                'total_response' => 0,
                'questions'      => [],
                'respondents'    => [],
            ];
        }

        $responses = QuestionnaireResponse::with(['answers', 'participant'])
            ->where('questionnaire_id', $questionnaire->id)
            ->orderBy('submitted_at')
            ->get();

        $answersByQuestion = $responses->flatMap(fn($r) => $r->answers)->groupBy('question_id');
        $isAnonymous = (bool) $questionnaire->is_anonymous;

        $questions = $questionnaire->questions->map(function ($question) use ($answersByQuestion, $responses) {
            $rows = $answersByQuestion->get($question->id, collect());

            return [
                'id'         => $question->id,
                'type'       => $question->type,
                'type_label' => QuestionnaireQuestion::TYPES[$question->type] ?? $question->type,
                'content'    => $question->content,
                // Pilihan jamak & skala menulis beberapa baris per responden,
                // jadi yang dihitung adalah jumlah respondennya.
                'answered'   => in_array($question->type, ['check', 'rate'], true)
                    ? $rows->pluck('response_id')->unique()->count()
                    : $rows->count(),
                'total'      => $responses->count(),
                'summary'    => $this->summarizeQuestion($question, $rows),
                'texts'      => in_array($question->type, QuestionnaireQuestion::CHOICE_TYPES, true)
                    || in_array($question->type, ['section', 'yesno'], true)
                    ? []
                    : $rows->pluck('value')->filter()->values(),
            ];
        })->values();

        return [
            'sub_title'      => $questionnaire->sub_title,
            'is_anonymous'   => $isAnonymous,
            'total_response' => $responses->count(),
            'questions'      => $questions,
            'respondents'    => $isAnonymous ? [] : $responses->map(fn($r) => [
                'name'         => $r->participant->name ?? '-',
                'nip'          => $r->participant ? get_user_meta($r->participant->id, 'nip') : null,
                'submitted_at' => optional($r->submitted_at)->format('d/m/Y H:i'),
            ])->values(),
        ];
    }

    /**
     * Ringkasan per pilihan: jumlah & persen. Untuk skala penilaian, tiap baris
     * dapat rata-rata nilainya. Tipe isian bebas tidak punya ringkasan (daftar
     * jawabannya ada di `texts`), kecuali `numeric` yang dirata-ratakan.
     */
    protected function summarizeQuestion(QuestionnaireQuestion $question, $rows): array
    {
        if ($question->type === 'section') {
            return [];
        }

        if ($question->type === 'yesno') {
            $total = $rows->count();

            return collect(['y' => 'Ya', 'n' => 'Tidak'])->map(function ($label, $value) use ($rows, $total) {
                $count = $rows->where('value', $value)->count();

                return [
                    'label'   => $label,
                    'total'   => $count,
                    'percent' => $total > 0 ? round($count / $total * 100, 1) : 0,
                ];
            })->values()->all();
        }

        if ($question->type === 'rate') {
            return $question->choices->map(function ($choice) use ($rows) {
                $values = $rows->where('choice_id', $choice->id)
                    ->pluck('value')
                    ->filter(fn($v) => $v !== null && $v !== '');

                return [
                    'label'   => $choice->content,
                    'total'   => $values->count(),
                    'percent' => null,
                    'average' => $values->count() > 0
                        ? round($values->map(fn($v) => (float) $v)->avg(), 2)
                        : null,
                ];
            })->values()->all();
        }

        if (in_array($question->type, ['radio', 'check', 'drop'], true)) {
            // Persentase pilihan jamak dihitung atas jumlah responden yang
            // menjawab, bukan jumlah centang, supaya totalnya bisa > 100%.
            $base = $question->type === 'check'
                ? $rows->pluck('response_id')->unique()->count()
                : $rows->count();

            return $question->choices->map(function ($choice) use ($rows, $base) {
                $count = $rows->where('choice_id', $choice->id)->count();

                return [
                    'label'   => $choice->content,
                    'total'   => $count,
                    'percent' => $base > 0 ? round($count / $base * 100, 1) : 0,
                ];
            })->values()->all();
        }

        if ($question->type === 'numeric') {
            $values = $rows->pluck('value')->filter(fn($v) => is_numeric($v))->map(fn($v) => (float) $v);

            if ($values->isEmpty()) {
                return [];
            }

            return [[
                'label'   => 'Rata-rata',
                'total'   => $values->count(),
                'percent' => null,
                'average' => round($values->avg(), 2),
            ]];
        }

        return [];
    }

    /**
     * Jawaban mentah untuk ekspor CSV: satu baris per responden, satu kolom
     * per pertanyaan.
     *
     * @return array{headers: array, rows: array}
     */
    public function getExportTable(Activity $activity): array
    {
        $questionnaire = Questionnaire::with('questions.choices')
            ->where('activity_id', $activity->id)
            ->firstOrFail();

        $questions = $questionnaire->questions
            ->reject(fn($q) => $q->type === 'section')
            ->values();

        $isAnonymous = (bool) $questionnaire->is_anonymous;

        $headers = $isAnonymous ? ['No', 'Waktu Pengisian'] : ['No', 'Nama', 'NIP', 'Waktu Pengisian'];
        foreach ($questions as $question) {
            $headers[] = strip_tags($question->content);
        }

        $responses = QuestionnaireResponse::with(['answers', 'participant'])
            ->where('questionnaire_id', $questionnaire->id)
            ->orderBy('submitted_at')
            ->get();

        $rows = [];
        foreach ($responses as $index => $response) {
            $answers = $response->answers->groupBy('question_id');

            $row = [$index + 1];
            if (!$isAnonymous) {
                $row[] = $response->participant->name ?? '-';
                $row[] = $response->participant ? get_user_meta($response->participant->id, 'nip') : '';
            }
            $row[] = optional($response->submitted_at)->format('Y-m-d H:i');

            foreach ($questions as $question) {
                $row[] = $this->formatAnswerForExport($question, $answers->get($question->id, collect()));
            }

            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    protected function formatAnswerForExport(QuestionnaireQuestion $question, $rows): string
    {
        if ($rows->isEmpty()) {
            return '';
        }

        if ($question->type === 'yesno') {
            return $rows->first()->value === 'y' ? 'Ya' : 'Tidak';
        }

        if (in_array($question->type, ['radio', 'drop', 'check'], true)) {
            return $question->choices
                ->whereIn('id', $rows->pluck('choice_id')->all())
                ->pluck('content')
                ->implode('; ');
        }

        if ($question->type === 'rate') {
            return $rows->map(function ($row) use ($question) {
                $choice = $question->choices->firstWhere('id', $row->choice_id);

                return ($choice->content ?? '-') . ': ' . $row->value;
            })->implode('; ');
        }

        return (string) $rows->first()->value;
    }

    /* ----------------------------------------------------------------------
     | Util
     * -------------------------------------------------------------------- */

    protected function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return array_key_exists($type, QuestionnaireQuestion::TYPES) ? $type : 'text';
    }
}
