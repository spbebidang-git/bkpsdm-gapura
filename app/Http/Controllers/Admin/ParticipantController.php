<?php

namespace App\Http\Controllers\Admin;

use App\Services\Identity\UserService;
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

                    $query->orderByRaw(
                        '(select value from user_meta where user_meta.user_id = users.id and user_meta.key = ? limit 1) '.$dir,
                        [$key]
                    );
                };
            };

            return DataTables::of($this->user->getParticipant())
                ->addIndexColumn()
                ->addColumn('nip', fn ($user) => $user->nip)
                ->addColumn(
                    'status_pegawai',
                    fn ($user) => $user->metas->firstWhere('key', 'status_pegawai')?->value
                )
                ->addColumn(
                    'jabatan',
                    fn ($user) => $user->metas->firstWhere('key', 'nama_jabatan')?->value
                )
                ->orderColumn('status_pegawai', $metaOrder('status_pegawai'))
                ->orderColumn('jabatan', $metaOrder('nama_jabatan'))
                ->only(['DT_RowIndex', 'nip', 'name', 'status_pegawai', 'jabatan', 'updated_at'])
                ->make();
        }

        return Inertia::render('Admin/Participant/Index');
    }
}
