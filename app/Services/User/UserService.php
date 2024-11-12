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
        // update_user_meta($participantId, 'fv_nama', $data['fv_nama']);
        update_user_meta($participantId, 'fc_glrdpn', $data['fc_glrdpn']);
        update_user_meta($participantId, 'fc_glrblk', $data['fc_glrblk']);
        update_user_meta($participantId, 'nip', $data['fc_nipbr']);
        update_user_meta($participantId, 'fc_TMTGol', $data['fc_TMTGol']);
        update_user_meta($participantId, 'fc_nik', $data['fc_nik']);
        // update_user_meta($participantId, 'fv_email', $data['fv_email']);
        update_user_meta($participantId, 'ruang', $data['ruang']);
        update_user_meta($participantId, 'fv_alamat', $data['fv_alamat']);
        update_user_meta($participantId, 'tmplhr', $data['tmplhr']);
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
        update_user_meta($participantId, 'kelamin', $data['kelamin']);
        update_user_meta($participantId, 'fv_tmplhr', $data['fv_tmplhr']);
        update_user_meta($participantId, 'fd_tgllhr', $data['fd_tgllhr']);
        update_user_meta($participantId, 'fv_Agama', $data['fv_Agama']);
        update_user_meta($participantId, 'fv_nmjur', $data['fv_nmjur']);
        update_user_meta($participantId, 'fc_thnlulus', $data['fc_thnlulus']);
        update_user_meta($participantId, 'fv_ket', $data['fv_ket']);
        update_user_meta($participantId, 'FV_TELP', $data['FV_TELP']);
        update_user_meta($participantId, 'FV_HP', $data['FV_HP']);
        update_user_meta($participantId, 'fc_kdJab', $data['fc_kdJab']);
        update_user_meta($participantId, 'nama_jabatan', $data['nama_jabatan']);
    }
}
