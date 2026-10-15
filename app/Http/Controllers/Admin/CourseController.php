<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Course;
use App\Models\Course\Trainer;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Repositories\Course\Contracts\CategoryRepositoryInterface;
use App\Repositories\Identity\Contracts\EmployeeStatusRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\CourseService;
use App\Services\Course\TrainerService;
use App\Models\Identity\Opd;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class CourseController
{
    public function __construct(
        protected CourseService $course,
        protected ActivityService $activity,
        protected TrainerService $trainer,
        protected CategoryRepositoryInterface $categories,
        protected EmployeeStatusRepositoryInterface $employeeStatuses,
        protected LearnRepositoryInterface $learns,
    ) {}

    public function index()
    {
        if ($this->isRestrictedTrainer(request()->user())) {
            abort_unless(request()->user()->opd?->is_active, 403, 'Trainer belum memiliki OPD aktif dari Kedaton.');
        }
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->course->getByAdmin($this->isRestrictedTrainer(request()->user()) ? request()->user()->id : null))
                ->filter(function ($instance) {
                    foreach (['status', 'type'] as $field) {
                        $value = request()->input('course_' . $field);
                        $allowed = $field === 'status' ? ['publish', 'draft'] : ['schedule', 'unschedule'];
                        if (in_array($value, $allowed, true)) {
                            $instance->collection = $instance->collection->where($field, $value)->values();
                        }
                    }
                }, true)
                ->addIndexColumn()
                ->make();
        }

        return Inertia::render('Admin/Course/Index');
    }

    public function overview(Course $course)
    {
        $this->assertCourseAccess($course);
        $course = $this->course->findForManagementOverview($course->id);
        abort_unless($course, 404);

        $finishedParticipant = $this->learns->getCompletedByCourse($course->id)
            ->map(fn ($learn) => [
                'name' => $learn->participant?->name,
                'job_title' => $learn->participant?->metas->first()?->value,
            ]);
        $onGoingParticipant = $this->learns->getOngoingByCourse($course->id)
            ->map(fn ($learn) => ['name' => $learn->participant?->name]);

        return Inertia::render('Admin/Course/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'type' => $course->type,
                'status' => $course->status,
                'jam_pelajaran' => $course->jam_pelajaran,
                'short_description' => $course->short_description,
                'thumbnail' => $course->thumbnail_src,
                'category' => $course->category?->title,
                'total_participant' => $course->total_participant,
                'total_passed' => $course->total_passed,
                'audience_scope' => $course->opd_id ? 'opd' : 'all',
                'opd_id' => $course->opd_id,
                'opd_name' => $course->opd?->name,
            ],
            'finishedParticipant' => $finishedParticipant,
            'onGoingParticipant' => $onGoingParticipant,
        ]);
    }

    public function create()
    {
        return Inertia::render('Instructor/Course/Create', [
            'employee_status' => $this->employeeStatuses->getAllOrderedByTitle(),
            'categories' => $this->categories->all(),
            ...$this->trainerFormProps(),
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        try {
            $data = $this->assignCurrentTrainer($request->validated());
            $this->course->createCourse($data);

            return $this->afterCourseMutation($request->input('type') === 'schedule'
                ? 'Kelas berhasil dibuat sebagai draft. Tambahkan jadwal dari menu Kelola jadwal.'
                : 'Kelas berhasil disimpan.');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Course store failed: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('danger', 'Terjadi kesalahan saat menyimpan data: ' . $th->getMessage());
        }
    }

    public function show(Course $course)
    {
        $this->assertCourseAccess($course);
        $course = $this->course->findForManagementDetail($course->id);
        abort_unless($course, 404);

        return Inertia::render('Instructor/Course/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
                'value' => $course->value,
                'short_description' => $course->short_description,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail_src,
                'total_participant' => $course->total_participant,
                'total_passed' => $course->total_passed,
                'efektifitas' => $course->efektifitas,
                'is_locked' => (bool)$course->is_locked,
                'category' => $course->category?->title,
                'type' => $course->type,
                'status' => $course->status,
                'jam_pelajaran' => $course->jam_pelajaran,
                'audience_scope' => $course->opd_id ? 'opd' : 'all',
                'opd_id' => $course->opd_id,
                'opd_name' => $course->opd?->name,
                'employee_status' => $course->employeeStatus->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                ]),
            ],
            'schedules' => $course->type === 'schedule' ? $course->schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'date' => $schedule->getRawOriginal('date'),
                'time_from' => $schedule->getRawOriginal('time_from') ? \Carbon\Carbon::parse($schedule->getRawOriginal('time_from'))->format('H:i') : null,
                'time_to' => $schedule->getRawOriginal('time_to') ? \Carbon\Carbon::parse($schedule->getRawOriginal('time_to'))->format('H:i') : null,
                'quota' => $schedule->getRawOriginal('quota'),
            ]) : [],
            'trainers' => $course->trainers->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'jabatan' => $t->jabatan,
                'photo_src' => $t->photo_src,
            ]),
        ]);
    }

    public function edit(Course $course)
    {
        $this->assertCourseAccess($course);
        $course = $this->course->findForManagementDetail($course->id);
        abort_unless($course, 404);

        // ambil id employee_status yg sudah terpilih
        $selectedEmployeeStatus = $course->employeeStatus->pluck('id')->all();

        return Inertia::render('Instructor/Course/Edit', [
            'course' => $course,
            'categories' => $this->categories->all(),
            'employee_status' => $this->employeeStatuses->getAllOrderedByTitle(),
            'selected_employee_status' => $selectedEmployeeStatus,
            'selected_trainers' => $course->trainers->pluck('id')->all(),
            ...$this->trainerFormProps(),
        ]);
    }

    public function update(Course $course, UpdateCourseRequest $request)
    {
        $this->assertCourseAccess($course);

        try {
            $data = $this->assignCurrentTrainer($request->validated());
            $this->course->updateCourse($course, $data);

            return $this->afterCourseMutation('Kelas berhasil diperbarui.');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Course update failed: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('danger', 'Terjadi kesalahan saat menyimpan data: ' . $th->getMessage());
        }
    }

    public function destroy(Course $course)
    {
        $this->assertCourseAccess($course);
        $this->course->deleteCourse($course);

        return $this->afterCourseMutation('Kelas berhasil dihapus.');
    }

    public function lock(Course $course)
    {
        $this->assertCourseAccess($course);
        if (! $this->course->lockCourse($course)) {
            return redirect()->back()->with('danger', "Materi sudah dikunci.");
        }

        return redirect()->back()->with('success', "Materi berhasil dikunci.");
    }

    protected function afterCourseMutation(string $message)
    {
        return to_route('admin.course.index')->with('success', $message);
    }

    protected function assertCourseAccess(Course $course): void
    {
        $user = request()->user();
        if (! $this->isRestrictedTrainer($user)) {
            return;
        }

        abort_unless($user->opd?->is_active && $course->opd_id === $user->opd_id, 403, 'Trainer hanya dapat mengelola kelas pada OPD sendiri.');

        abort_unless($user?->hasRole('trainer') && $this->course->canBeManagedByTrainer($course, $user->id), 403);
    }

    protected function isRestrictedTrainer($user): bool
    {
        if (! $user?->hasRole('trainer')) {
            return false;
        }

        return ! $user->hasAnyRole(['super_admin', 'administrator']);
    }

    /** @return array<string, mixed> */
    protected function trainerFormProps(): array
    {
        $user = request()->user();

        if (! $this->isRestrictedTrainer($user)) {
            return [
                'is_trainer' => false,
                'trainers' => $this->trainer->getForForm(),
            ];
        }

        $trainer = $this->trainer->getOrCreateForUser($user);

        return [
            'is_trainer' => true,
            'trainers' => [],
            'selected_trainers' => [$trainer->id],
        ];
    }

    /** @param array<string, mixed> $data */
    protected function assignCurrentTrainer(array $data): array
    {
        $user = request()->user();

        if ($this->isRestrictedTrainer($user)) {
            abort_unless($user->opd?->is_active, 403, 'Trainer harus memiliki OPD aktif dari Kedaton. Silakan login ulang setelah data OPD tersedia.');
            $data['trainers'] = [$this->trainer->getOrCreateForUser($user)->id];
            $data['opd_id'] = $user->opd_id;
        } else {
            $trainers = Trainer::with(['user.opd', 'opd'])->whereIn('id', $data['trainers'] ?? [])->get();
            $opdIds = $trainers->pluck('organization_id')->filter()->unique();
            if ($trainers->count() !== count($data['trainers'] ?? []) || $opdIds->count() !== 1
                || ! Opd::whereKey($opdIds->first())->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['trainers' => 'Pilih trainer dari satu OPD aktif yang sama.']);
            }
            $data['opd_id'] = $opdIds->first();
        }

        return $data;
    }
}
