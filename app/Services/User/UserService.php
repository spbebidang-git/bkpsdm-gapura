<?php

namespace App\Services\User;

use App\Helper\UploadMedia;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getParticipant()
    {
        return User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["Participant"]);
        })->orderBy('id')->get();
    }

    public function getInstructor()
    {
        return User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["Instructor"]);
        })->orderBy('id')->get();
    }

    public function getNip($userId)
    {
        $user = User::find($userId);

        $nip = strtok($user->email, '@');

        if ($nip == 'elearning') {
            return 199210212019031005;
        }

        return $nip;
    }

    public function storeUserMeta($participantId, $data)
    {
        update_user_meta($participantId, 'nip', $data['fc_nipbr']);
        update_user_meta($participantId, 'uker', $data['uker']);
        update_user_meta($participantId, 'fc_kduker', $data['fc_kduker']);
        update_user_meta($participantId, 'satker', $data['satker']);
        update_user_meta($participantId, 'fc_kdsatker', $data['fc_kdsatker']);
        update_user_meta($participantId, 'subsatker', $data['subsatker']);
        update_user_meta($participantId, 'fc_kdsubsatker', $data['fc_kdsubsatker']);
        update_user_meta($participantId, 'seksi', $data['seksi']);
        update_user_meta($participantId, 'fc_kdseksi', $data['fc_kdseksi']);
        update_user_meta($participantId, 'subseksi', $data['subseksi']);
        update_user_meta($participantId, 'fc_kdsubseksi', $data['fc_kdsubseksi']);
        update_user_meta($participantId, 'fc_kdJab', $data['fc_kdJab']);
        update_user_meta($participantId, 'nama_jabatan', $data['nama_jabatan']);
        update_user_meta($participantId, 'status_pegawai', $data['status_pegawai']);
    }
}
