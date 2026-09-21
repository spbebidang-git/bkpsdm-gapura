<?php

namespace App\Http\Controllers\Participant;

use App\Services\Identity\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController
{
    public function index()
    {
        $user = request()->user();

        $profile = [
            'nip' => get_user_meta($user->id, 'nip') ? get_user_meta($user->id, 'nip') : '-',
            'name' => $user->name,
            'status_pegawai' => get_user_meta($user->id, 'status_pegawai') ? trim(get_user_meta($user->id, 'status_pegawai')) : '-',
            'jabatan' => get_user_meta($user->id, 'nama_jabatan') ? get_user_meta($user->id, 'nama_jabatan') : '-',
        ];

        return Inertia::render('Participant/Profile/Index', [
            'profile' => $profile,
        ]);
    }
}
