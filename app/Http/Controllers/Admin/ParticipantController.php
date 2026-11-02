<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ParticipantStoreRequest;
use App\Http\Requests\Admin\ParticipantUpdateRequest;
use App\Models\Identity\User;
use App\Services\Identity\AdminParticipantService;
use App\Services\Identity\UserService;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ParticipantController
{
    public function __construct(protected UserService $user, private readonly AdminParticipantService $participants) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            $metaOrder = fn (string $key) => fn ($query, $order) => $this->participants->orderMeta($query, $key, (string) $order);

            return DataTables::of($this->user->getParticipant())
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
        return Inertia::render('Admin/Participant/Form', $this->participants->options(['participant' => null]));
    }

    public function store(ParticipantStoreRequest $request)
    {
        $data = $request->validated();

        $this->participants->create($data);

        return to_route('admin.participant.index')->with('success', 'Pengguna lokal berhasil ditambahkan.');
    }

    public function edit(User $participant)
    {
        $this->ensureLocalParticipant($participant);
        $this->participants->load($participant);

        return Inertia::render('Admin/Participant/Form', $this->participants->options([
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

    public function update(ParticipantUpdateRequest $request, User $participant)
    {
        $this->ensureLocalParticipant($participant);
        $data = $request->validated();

        $this->participants->update($participant, $data);

        return to_route('admin.participant.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $participant)
    {
        $this->ensureLocalParticipant($participant);

        $this->participants->delete($participant);

        return to_route('admin.participant.index')->with('success', 'Pengguna lokal berhasil dihapus.');
    }

    private function ensureLocalParticipant(User $participant): void
    {
        abort_unless($participant->usesLocalAuthentication() && $participant->hasRole('participant'), 404);
    }
}
