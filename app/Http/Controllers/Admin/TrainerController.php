<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course\Trainer;
use App\Models\Identity\Opd;
use App\Models\Identity\User;
use App\Services\Course\TrainerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class TrainerController extends Controller
{
    public function __construct(protected TrainerService $trainers) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            $activeOpdIds = Opd::where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
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
            'opds' => Opd::orderBy('name')->get(['id', 'name']),
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
            'opds' => Opd::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateTrainer($request);

        $this->trainers->create($data);

        return to_route('admin.trainer.index')->with('success', $data['source'] === 'user'
            ? 'Pengguna berhasil didaftarkan sebagai pemateri.'
            : 'Pemateri berhasil ditambahkan.');
    }

    public function edit(Trainer $trainer)
    {
        if ($trainer->user_id) {
            abort_unless(request()->user()?->hasAnyRole(['super_admin', 'administrator']), 403);

            $trainer->loadMissing(['user.opd', 'user.metas', 'opds']);
            $user = $trainer->user;
            $activeOpds = Opd::where('is_active', true)->orderBy('name')->get(['id', 'name']);

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

        $activeOpds = Opd::where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    public function update(Request $request, Trainer $trainer)
    {
        $data = $this->validateTrainer($request, $trainer);

        $this->trainers->update($trainer, $data);

        return to_route('admin.trainer.index')->with('success', 'Pemateri berhasil diperbarui.');
    }

    public function destroy(Trainer $trainer)
    {
        $this->trainers->delete($trainer->id);

        return to_route('admin.trainer.index')->with('success', 'Pemateri berhasil dihapus.');
    }

    protected function validateTrainer(Request $request, ?Trainer $trainer = null): array
    {
        if (! $request->filled('opd_ids') && $request->filled('opd_id')) {
            $request->merge(['opd_ids' => [(int) $request->input('opd_id')]]);
        }
        if ($trainer?->user_id) {
            abort_unless($request->user()?->hasAnyRole(['super_admin', 'administrator']), 403);
        }
        $source = $request->input('source');
        $isUser = $source === 'user';
        $isRegistration = $isUser && ! $trainer;
        $localAccount = $source === 'local';
        $editingLocalAccount = $trainer?->user?->usesLocalAuthentication() ?? false;

        return $request->validate([
            'source' => ['required', Rule::in($trainer?->user_id ? ['user'] : ($trainer ? ['local'] : ['local', 'user']))],
            'user_id' => $isRegistration ? ['required', 'ulid', Rule::exists('users', 'id')->whereNull('deleted_at')]
                : ['prohibited'],
            'name' => $isRegistration ? ['prohibited'] : ['required', 'string', 'max:255'],
            'jabatan' => $isRegistration ? ['prohibited'] : ['nullable', 'string', 'max:255'],
            'opd_ids' => ['required', 'array', 'min:1'],
            'opd_ids.*' => ['required', 'integer', 'distinct', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'photo' => $isRegistration ? ['prohibited'] : ['nullable', 'image', 'max:2048', 'mimes:png,jpg,jpeg'],
            'email' => $localAccount ? ['required', 'email', 'max:255', Rule::unique('users', 'email')]
                : ($editingLocalAccount ? ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($trainer->user_id)] : ['prohibited']),
            'password' => $localAccount ? ['required', 'string', 'min:8', 'confirmed']
                : ($editingLocalAccount ? ['nullable', 'string', 'min:8', 'confirmed'] : ['prohibited']),
        ], [], ['source' => 'Sumber pemateri', 'user_id' => 'Pengguna', 'name' => 'Nama pemateri', 'jabatan' => 'Jabatan', 'photo' => 'Foto pemateri', 'opd_ids' => 'OPD pemateri']);
    }
}
