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
                ->rawColumns(['action'])
                ->make();
        }

        return view('admin.participant.index');
    }
}
