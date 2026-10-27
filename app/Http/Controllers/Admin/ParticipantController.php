<?php

namespace App\Http\Controllers\Admin;

use App\Models\Identity\User;
use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\Opd;
use App\Services\Identity\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ParticipantController
{
    public function __construct(protected UserService $user) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            $metaOrder = function (string $key) {
                return function ($query, $order) use ($key) {
                    $dir = strtolower((string) $order) === 'desc' ? 'desc' : 'asc';
                    if ($key === 'status_pegawai') {
                        $query->orderByRaw('coalesce((select title from employee_status where employee_status.id = users.employee_status_id limit 1), (select value from user_meta where user_meta.user_id = users.id and user_meta.key = ? limit 1)) '.$dir, [$key]);
                    } else {
                        $query->orderByRaw('(select value from user_meta where user_meta.user_id = users.id and user_meta.key = ? limit 1) '.$dir, [$key]);
                    }
                };
            };

            return DataTables::of($this->user->getParticipant()->with(['employeeStatus', 'opd']))
                ->addIndexColumn()
                ->addColumn('nip', fn ($user) => $user->nip)
                ->addColumn('status_pegawai', fn ($user) => $user->employeeStatus?->title ?? $user->metas->firstWhere('key', 'status_pegawai')?->value)
                ->addColumn('jabatan', fn ($user) => $user->metas->firstWhere('key', 'nama_jabatan')?->value)
                ->addColumn('opd', fn ($user) => $user->opd?->name)
                ->addColumn('can_manage', fn ($user) => $user->usesLocalAuthentication())
                ->orderColumn('status_pegawai', $metaOrder('status_pegawai'))
                ->orderColumn('jabatan', $metaOrder('nama_jabatan'))
                ->only(['DT_RowIndex', 'id', 'nip', 'name', 'status_pegawai', 'jabatan', 'opd', 'updated_at', 'can_manage'])
                ->make();
        }

        return Inertia::render('Admin/Participant/Index');
    }

    public function create()
    {
        return Inertia::render('Admin/Participant/Form', $this->formOptions(['participant' => null]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nip' => ['nullable', 'string', 'max:255', 'unique:users,nip'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'employee_status_id' => ['nullable', 'string', Rule::exists('employee_status', 'id')->whereNull('deleted_at')],
            'opd_id' => ['nullable', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ]);

        $participant = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'nip' => $data['nip'] ?: null,
            'password' => Hash::make($data['password']),
            'auth_source' => User::AUTH_SOURCE_LOCAL,
            'employee_status_id' => $data['employee_status_id'] ?? null,
            'opd_id' => $data['opd_id'] ?? null,
        ]);
        $participant->assignRole('participant');
        $this->saveProfileMeta($participant, $data);

        return to_route('admin.participant.index')->with('success', 'Pengguna lokal berhasil ditambahkan.');
    }

    public function edit(User $participant)
    {
        $this->ensureLocalParticipant($participant);
        $participant->load(['metas', 'employeeStatus']);

        return Inertia::render('Admin/Participant/Form', $this->formOptions([
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
                'nip' => $participant->nip,
                'employee_status_id' => $participant->employee_status_id,
                'opd_id' => $participant->opd_id,
                'status_pegawai' => $participant->employeeStatus?->title ?? $participant->metas->firstWhere('key', 'status_pegawai')?->value,
                'jabatan' => $participant->metas->firstWhere('key', 'nama_jabatan')?->value,
            ],
        ]));
    }

    public function update(Request $request, User $participant)
    {
        $this->ensureLocalParticipant($participant);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($participant->id)],
            'nip' => ['nullable', 'string', 'max:255', Rule::unique('users', 'nip')->ignore($participant->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'employee_status_id' => ['nullable', 'string', Rule::exists('employee_status', 'id')->whereNull('deleted_at')],
            'opd_id' => ['nullable', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ]);

        $participant->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'nip' => $data['nip'] ?: null,
            'employee_status_id' => $data['employee_status_id'] ?? null,
            'opd_id' => $data['opd_id'] ?? null,
        ]);
        if (filled($data['password'] ?? null)) {
            $participant->password = Hash::make($data['password']);
        }
        $participant->save();
        $this->saveProfileMeta($participant, $data);

        return to_route('admin.participant.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $participant)
    {
        $this->ensureLocalParticipant($participant);

        DB::transaction(function () use ($participant) {
            $participant->removeRole('participant');
            if (! $participant->roles()->exists()) {
                $participant->delete();
            }
        });

        return to_route('admin.participant.index')->with('success', 'Pengguna lokal berhasil dihapus.');
    }

    private function ensureLocalParticipant(User $participant): void
    {
        abort_unless($participant->usesLocalAuthentication() && $participant->hasRole('participant'), 404);
    }

    private function saveProfileMeta(User $participant, array $data): void
    {
        $statusTitle = isset($data['employee_status_id'])
            ? EmployeeStatus::whereKey($data['employee_status_id'])->value('title')
            : '';
        foreach (['status_pegawai' => strtoupper(trim($statusTitle ?? '')), 'nama_jabatan' => trim($data['jabatan'] ?? '')] as $key => $value) {
            if ($value === '') {
                $participant->metas()->where('key', $key)->delete();
            } else {
                update_user_meta($participant->id, $key, $value);
            }
        }
    }

    private function formOptions(array $props): array
    {
        return [
            ...$props,
            'employeeStatuses' => EmployeeStatus::orderBy('title')->get(['id', 'title']),
            'opds' => Opd::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ];
    }
}
