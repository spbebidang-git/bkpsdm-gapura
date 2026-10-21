<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;
use App\Models\Course\Trainer;

abstract class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string'],
            'category' => ['required', 'string', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'employee_status' => ['required', 'array', 'min:1'],
            'employee_status.*' => ['exists:employee_status,id'],
            'title' => ['required', 'string'],
            'short_description' => ['required', 'string'],
            'description' => ['required', 'string'],
            'thumbnail' => $this->thumbnailRules(),
            'teaser_video' => ['nullable', 'string', 'regex:%^https://(www\.)?youtube\.com/embed/[\w-]+(\?.*)?$%i'],
            'expired' => ['nullable', 'required_if:type,unschedule', 'numeric', 'min:0'],
            'status' => ['required', 'string'],
            'jam_pelajaran' => ['required', 'numeric', 'min:0'],
            'trainers' => [$this->isRestrictedTrainer() ? 'nullable' : 'required', 'array', 'min:1'],
            'trainers.*' => ['string', 'distinct', Rule::exists('trainers', 'id')->whereNull('deleted_at')],
            'opd_id' => ['nullable', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_if' => ':attribute wajib diisi untuk jenis kelas yang dipilih.',
            'string' => ':attribute harus berupa teks.',
            'numeric' => ':attribute harus berupa angka.',
            'array' => ':attribute harus berupa daftar.',
            'min' => ':attribute minimal :min.',
            'image' => ':attribute harus berupa gambar.',
            'max' => ':attribute tidak boleh lebih dari :max KB.',
            'mimes' => ':attribute harus berformat: :values.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'teaser_video.regex' => 'Tautan video teaser harus berupa tautan embed YouTube yang valid (contoh: https://www.youtube.com/embed/VIDEO_ID).',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (trim(strip_tags((string) $this->input('description'))) === '') {
                $validator->errors()->add('description', 'Deskripsi lengkap wajib diisi.');
            }

            if ($validator->errors()->isEmpty()) {
                if ($this->isRestrictedTrainer()) {
                    $allowed = Trainer::activeOpdIdsForUser($this->user()->id);
                    $opdId = (int) ($this->input('opd_id') ?: ($allowed[0] ?? 0));
                    if (! in_array($opdId, $allowed, true)) {
                        $validator->errors()->add('opd_id', 'Pilih OPD yang ditetapkan untuk akun pemateri.');
                    }
                } else {
                    $trainers = Trainer::with(['user.opd', 'opd', 'opds'])->whereIn('id', $this->input('trainers', []))->get();
                    $opdId = (int) ($this->input('opd_id') ?: $trainers->first()?->organization_id);
                    if ($trainers->isEmpty() || $trainers->contains(fn ($trainer) => ! in_array($opdId, $trainer->organization_ids, true))
                        || ! \App\Models\Identity\Opd::whereKey($opdId)->where('is_active', true)->exists()) {
                        $validator->errors()->add('trainers', 'Pilih pemateri yang terdaftar pada OPD kelas.');
                    }
                }
            }

            if ($this->input('type') === 'schedule' && $this->input('status') === 'publish') {
                $course = $this->route('course');
                if (! $course || $course->schedules()->count() === 0) {
                    $validator->errors()->add('status', 'Tambahkan minimal satu jadwal sebelum memublikasikan kelas terjadwal.');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'type' => 'Jenis kelas',
            'category' => 'Kategori',
            'employee_status' => 'Status peserta',
            'employee_status.*' => 'Status peserta',
            'title' => 'Judul kelas',
            'short_description' => 'Deskripsi singkat',
            'description' => 'Deskripsi lengkap',
            'thumbnail' => 'Gambar sampul',
            'teaser_video' => 'Tautan video teaser',
            'expired' => 'Masa kedaluwarsa',
            'status' => 'Status publikasi',
            'jam_pelajaran' => 'Jam pelajaran',
            'trainers' => 'Pemateri',
            'trainers.*' => 'Pemateri',
        ];
    }

    private function isRestrictedTrainer(): bool
    {
        $user = $this->user();

        return $user?->hasRole('trainer') && ! $user->hasAnyRole(['super_admin', 'administrator']);
    }

    /** @return array<int, string> */
    abstract protected function thumbnailRules(): array;
}
