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
            $trainers = $this->trainers->getForAdmin();
            if (request()->filled('opd_id')) {
                $trainers = $trainers->filter(fn ($trainer) => (string) $trainer->organization_id === (string) request('opd_id'))->values();
            }
            return DataTables::of($trainers)
                ->addIndexColumn()
                ->addColumn('photo_url', fn ($row) => $row->photo_src)
                ->addColumn('opd_name', fn ($row) => $row->organization_name)
                ->addColumn('source', fn ($row) => $row->user_id ? 'sso' : 'local')
                ->addColumn('source_label', fn ($row) => $row->user_id
                    ? ($row->user?->usesSsoAuthentication() ? 'Kedaton SSO' : ($row->user?->usesPrestigeAuthentication() ? 'Pengguna Prestige' : 'Pengguna GAPURA'))
                    : 'Lokal')
                ->addColumn('user_auth_source', fn ($row) => $row->user?->auth_source)
                ->only(['DT_RowIndex', 'id', 'name', 'jabatan', 'photo_url', 'opd_name', 'source', 'source_label', 'user_id', 'user_auth_source', 'updated_at'])
                ->make();
        }

        return Inertia::render('Admin/Trainer/Index', [
            'opds' => Opd::orderBy('name')->get(['id', 'name']),
            'isSuperAdmin' => request()->user()?->hasRole('super_admin') ?? false,
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
            ? 'Pengguna berhasil didaftarkan sebagai trainer.'
            : 'Trainer berhasil ditambahkan.');
    }

    public function edit(Trainer $trainer)
    {
        if ($trainer->user_id) {
            abort_unless(request()->user()?->hasRole('super_admin'), 403);

            $trainer->loadMissing(['user.opd', 'user.metas']);
            $user = $trainer->user;

            return Inertia::render('Admin/Trainer/Form', [
                'opds' => Opd::where('is_active', true)->orWhere('id', $user?->opd_id)->orderBy('name')->get(['id', 'name']),
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $user?->name ?? $trainer->name,
                    'jabatan' => $user?->metas->firstWhere('key', 'nama_jabatan')?->value,
                    'photo_src' => $user?->profile_picture,
                    'source' => 'user',
                    'user_id' => $trainer->user_id,
                    'nip' => $user?->nip,
                    'opd' => $user?->opd?->name,
                    'opd_id' => $user?->opd_id,
                ],
            ]);
        }

        return Inertia::render('Admin/Trainer/Form', [
            'opds' => Opd::where('is_active', true)->orWhere('id', $trainer->organization_id)->orderBy('name')->get(['id', 'name']),
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
            ],
        ]);
    }

    public function update(Request $request, Trainer $trainer)
    {
        $data = $this->validateTrainer($request, $trainer);

        $this->trainers->update($trainer, $data);

        return to_route('admin.trainer.index')->with('success', 'Trainer berhasil diperbarui.');
    }

    public function destroy(Trainer $trainer)
    {
        $this->trainers->delete($trainer->id);

        return to_route('admin.trainer.index')->with('success', 'Trainer berhasil dihapus.');
    }

    protected function validateTrainer(Request $request, ?Trainer $trainer = null): array
    {
        if ($trainer?->user_id) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);
        }
        $source = $request->input('source');
        $isUser = $source === 'user';
        $isRegistration = $isUser && ! $trainer;
        $registrationUser = $isRegistration ? User::query()->find($request->input('user_id')) : null;
        $registrationNeedsOpd = $registrationUser?->usesLocalAuthentication() ?? false;

        return $request->validate([
            'source' => ['required', Rule::in($trainer?->user_id ? ['user'] : ($trainer ? ['local'] : ['local', 'user']))],
            'user_id' => $isRegistration ? ['required', 'ulid', Rule::exists('users', 'id')->whereNull('deleted_at')]
                : ['prohibited'],
            'name' => $isRegistration ? ['prohibited'] : ['required', 'string', 'max:255'],
            'jabatan' => $isRegistration ? ['prohibited'] : ['nullable', 'string', 'max:255'],
            'opd_id' => $isRegistration
                ? ($registrationNeedsOpd
                    ? ['required', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)]
                    : ['prohibited'])
                : ['required', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'photo' => $isRegistration ? ['prohibited'] : ['nullable', 'image', 'max:2048', 'mimes:png,jpg,jpeg'],
        ], [], ['source' => 'Sumber trainer', 'user_id' => 'Pengguna', 'name' => 'Nama trainer', 'jabatan' => 'Jabatan', 'photo' => 'Foto trainer', 'opd_id' => 'OPD trainer']);
    }
}
