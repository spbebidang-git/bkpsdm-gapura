<?php

namespace App\Services\Course\Activity\Question;

use App\Imports\VariantImport;
use App\Models\Course\Activity\Assessment\Question;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class QuestionImportService
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questions,
        private readonly QuestionVariantRepositoryInterface $variants,
    ) {}

    public function canImport(string $activityId): bool
    {
        $question = $this->questions->findByActivity($activityId);
        abort_unless($question, 404);
        return ! $question->has_imported && $this->variants->getByQuestion($question->id)->isEmpty();
    }

    public function import(string $activityId, UploadedFile $file): void
    {
        DB::transaction(function () use ($activityId, $file): void {
            $question = $this->questions->lockByActivity($activityId);
            if ($question->has_imported || $this->variants->getByQuestion($question->id)->isNotEmpty()) {
                throw ValidationException::withMessages(['content' => 'Impor hanya tersedia sebelum varian dibuat dan sebelum impor pertama.']);
            }
            Excel::import(new VariantImport($question->id), $file);
            $variants = $this->variants->getByQuestion($question->id)->pluck('id', 'code');
            if ($variants->isEmpty()) {
                throw ValidationException::withMessages(['content' => 'File tidak berisi varian soal.']);
            }
            Excel::import(new VariantImport($variants), $file);
            $this->questions->update($question, ['has_imported' => 1]);
        });
    }
}
