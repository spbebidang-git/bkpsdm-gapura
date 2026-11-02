<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrainerRequest;
use App\Models\Course\Trainer;
use App\Services\Course\TrainerService;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class TrainerController extends Controller
{
    public function __construct(protected TrainerService $trainers) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            $activeOpdIds = $this->trainers->opdOptions()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $trainers = $this->trainers->getForAdmin();
            if (request()->filled('opd_id')) {
                $trainers = $trainers->filter(fn ($trainer) => in_array((int) request('opd_id'), $trainer->organization_ids, true))->values();
            }

            return DataTables::of($trainers)
                ->addIndexColumn()
                ->addColumn('photo_url', fn ($row) => $row->photo_src)
                ->addColumn('opd_name', fn ($row) => $row->organization_name)
                ->addColumn('opd_names', fn ($row) => $row->organization_names)
                ->addColumn('opd_ids', fn ($row) => $row->organization_ids)
                ->addColumn('all_opds', fn ($row) => count($activeOpdIds) > 0 && count(array_diff($activeOpdIds, $row->organization_ids)) === 0)
                ->addColumn('source', fn ($row) => $row->user_id ? 'user' : 'local')
                ->addColumn('source_label', fn ($row) => $row->user_id
                    ? ($row->user?->usesSsoAuthentication() ? 'Kedaton SSO' : ($row->user?->usesPrestigeAuthentication() ? 'Pengguna Prestige' : 'Akun lokal'))
                    : 'Lokal')
                ->addColumn('user_auth_source', fn ($row) => $row->user?->auth_source)
                ->only(['DT_RowIndex', 'id', 'name', 'jabatan', 'photo_url', 'opd_name', 'opd_names', 'opd_ids', 'all_opds', 'source', 'source_label', 'user_id', 'user_auth_source', 'updated_at'])
                ->make();
        }

        return Inertia::render('Admin/Trainer/Index', [
            'opds' => $this->trainers->opdOptions(false),
            'canEditLinkedUser' => request()->user()?->hasAnyRole(['super_admin', 'administrator']) ?? false,
        ]);
    }

    public function create()
    {
        $mode = request()->query('mode') === 'user' ? 'user' : 'local';

        return Inertia::render('Admin/Trainer/Form', [
            'trainer' => null,
            'mode' => $mode,
            'users' => $mode === 'user' ? $this->trainers->getEligibleUsers() : [],
            'opds' => $this->trainers->opdOptions(),
        ]);
    }

    public function store(TrainerRequest $request)
    {
        $data = $request->validated();

        $this->trainers->create($data);

        return to_route('admin.trainer.index')->with('success', $data['source'] === 'user'
            ? 'Pengguna berhasil didaftarkan sebagai pemateri.'
            : 'Pemateri berhasil ditambahkan.');
    }

    public function edit(Trainer $trainer)
    {
        if ($trainer->user_id) {
            abort_unless(request()->user()?->hasAnyRole(['super_admin', 'administrator']), 403);

            $this->trainers->loadForAdmin($trainer);
            $user = $trainer->user;
            $activeOpds = $this->trainers->opdOptions();

            return Inertia::render('Admin/Trainer/Form', [
                'opds' => $activeOpds,
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $user?->name ?? $trainer->name,
                    'jabatan' => $user?->metas->firstWhere('key', 'nama_jabatan')?->value,
                    'photo_src' => $user?->profile_picture,
                    'source' => 'user',
                    'user_id' => $trainer->user_id,
                    'email' => $user?->email,
                    'auth_source' => $user?->auth_source,
                    'nip' => $user?->nip,
                    'opd' => $user?->opd?->name,
                    'opd_id' => $user?->opd_id,
                    'opd_ids' => array_values(array_intersect($trainer->organization_ids, $activeOpds->pluck('id')->map(fn ($id) => (int) $id)->all())),
                ],
            ]);
        }

        $activeOpds = $this->trainers->opdOptions();

        return Inertia::render('Admin/Trainer/Form', [
            'opds' => $activeOpds,
            'trainer' => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'jabatan' => $trainer->jabatan,
                'photo' => $trainer->photo,
                'photo_src' => $trainer->photo_src,
                'source' => $trainer->user_id ? 'sso' : 'local',
                'user_id' => $trainer->user_id,
                'nip' => $trainer->user?->nip,
                'opd' => $trainer->organization_name,
                'opd_id' => $trainer->organization_id,
                'opd_ids' => array_values(array_intersect($trainer->organization_ids, $activeOpds->pluck('id')->map(fn ($id) => (int) $id)->all())),
            ],
        ]);
    }

    public function update(TrainerRequest $request, Trainer $trainer)
    {
        $data = $request->validated();

        $this->trainers->update($trainer, $data);

        return to_route('admin.trainer.index')->with('success', 'Pemateri berhasil diperbarui.');
    }

    public function destroy(Trainer $trainer)
    {
        $this->trainers->delete($trainer->id);

        return to_route('admin.trainer.index')->with('success', 'Pemateri berhasil dihapus.');
    }
}
