<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function getParticipant()
    {
        return User::with(['roles', 'metas'])->whereHas('roles', function ($q) {
            $q->whereIn('name', ['Participant']);
        })->orderBy('id');
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

    public function storeUserMeta($participantId, array $data, ?string $loginNip = null)
    {
        $nip = $this->resolveParticipantNip($participantId, $data, $loginNip);

        if ($nip !== null) {
            update_user_meta($participantId, 'nip', $nip);
        }

        if (array_key_exists('status_pegawai', $data) && filled($data['status_pegawai'])) {
            update_user_meta($participantId, 'status_pegawai', $data['status_pegawai']);
        } elseif (array_key_exists('fv_status_pegawai', $data) && filled($data['fv_status_pegawai'])) {
            update_user_meta($participantId, 'status_pegawai', $data['fv_status_pegawai']);
        } elseif (array_key_exists('fc_status_pegawai', $data) && filled($data['fc_status_pegawai'])) {
            update_user_meta($participantId, 'status_pegawai', $data['fc_status_pegawai']);
        }

        // Bersihkan data meta lama yang sudah tidak dibutuhkan
        // Hanya pertahankan nip, status_pegawai, nip_hash, dan key numerik (variant)
        $protectedKeys = ['nip', 'status_pegawai', 'nip_hash'];

        $existingMetas = \App\Models\Management\UserMeta::where('user_id', $participantId)->get();

        foreach ($existingMetas as $meta) {
            $key = $meta->key;

            // Jangan hapus jika key berupa angka (untuk id variant soal)
            if (is_numeric($key)) {
                continue;
            }

            // Hapus jika bukan key terlindungi
            if (!in_array($key, $protectedKeys)) {
                $meta->delete();
            }
        }
    }

    private function resolveParticipantNip($participantId, array $data, ?string $loginNip = null): ?string
    {
        $candidates = [
            $loginNip,
            $data['fc_nipbr'] ?? null,
            $data['nip'] ?? null,
            $data['fv_nipbr'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_scalar($candidate)) {
                continue;
            }

            $candidate = trim((string) $candidate);

            if ($candidate === '' || $this->looksLikeAccessToken($candidate)) {
                continue;
            }

            return $candidate;
        }

        Log::warning('User meta sync skipped NIP update because no valid NIP candidate was found.', [
            'participant_id' => $participantId,
            'keys' => array_keys($data),
        ]);

        return null;
    }

    private function looksLikeAccessToken(string $value): bool
    {
        if (str_starts_with(strtolower($value), 'bearer ')) {
            return true;
        }

        if (preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $value) === 1) {
            return true;
        }

        return strlen($value) > 80 && preg_match('/^[A-Za-z0-9\-_.]+$/', $value) === 1;
    }
}
