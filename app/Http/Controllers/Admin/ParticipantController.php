<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ParticipantController
{
    protected $user;

    public function __construct(UserService $userService)
    {
        $this->user = $userService;
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->user->getParticipant())
                ->addIndexColumn()
                ->addColumn('nip', fn($user) => $user->nip)
                ->addColumn('status_pegawai', fn($user) => $user->metas->firstWhere('key', 'status_pegawai')?->value ?? '-')
                ->addColumn('jabatan', fn($user) => $user->metas->firstWhere('key', 'nama_jabatan')?->value ?? '-')
                ->rawColumns(['action'])
                ->make();
        }

        return view('admin.participant.index');
    }
}
