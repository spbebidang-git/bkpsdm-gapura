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
        update_user_meta($participantId, 'status_pegawai', $data['status_pegawai']);
    }
}
