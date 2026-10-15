<?php

namespace App\Services\Identity;

use App\Repositories\Identity\Contracts\UserMetaRepositoryInterface;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use App\Models\Identity\Opd;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserMetaRepositoryInterface $userMetas,
    ) {}

    public function getParticipant()
    {
        return $this->users->queryByRole('participant', ['roles', 'metas'])->orderBy('id');
    }

    public function getInstructor()
    {
        return $this->users->getByRole('administrator', ['roles'])->sortBy('id')->values();
    }

    public function getNip($userId)
    {
        $user = $this->users->findOrFail($userId);

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

        $this->syncUserOpd($participantId, $data);

        if (array_key_exists('status_pegawai', $data) && filled($data['status_pegawai'])) {
            update_user_meta($participantId, 'status_pegawai', strtoupper(trim($data['status_pegawai'])));
        } elseif (array_key_exists('fv_status_pegawai', $data) && filled($data['fv_status_pegawai'])) {
            update_user_meta($participantId, 'status_pegawai', strtoupper(trim($data['fv_status_pegawai'])));
        } elseif (array_key_exists('fc_status_pegawai', $data) && filled($data['fc_status_pegawai'])) {
            update_user_meta($participantId, 'status_pegawai', strtoupper(trim($data['fc_status_pegawai'])));
        }

        $jabatan = $data['nama_jabatan'] ?? $data['fv_nama_jabatan'] ?? $data['fc_nama_jabatan'] ?? null;
        if (filled($jabatan)) {
            update_user_meta($participantId, 'nama_jabatan', trim($jabatan));
        }

        // Bersihkan meta lama; pertahankan key penting + key numerik (variant soal)
        $protectedKeys = array_values(array_unique(array_merge(
            ['nip', 'status_pegawai', 'nama_jabatan', 'nip_hash'],
            config('services.kedaton.employee_opd_code_fields', []),
            config('services.kedaton.employee_opd_name_fields', []),
        )));

        $existingMetas = $this->userMetas->getByUser($participantId);

        foreach ($existingMetas as $meta) {
            $key = $meta->key;

            // Jangan hapus jika key berupa angka (untuk id variant soal)
            if (is_numeric($key)) {
                continue;
            }

            // Hapus jika bukan key terlindungi
            if (! in_array($key, $protectedKeys)) {
                $this->userMetas->delete($meta);
            }
        }
    }

    public function syncUserOpd(int|string $userId, array $data): ?Opd
    {
        $code = $this->firstScalar($data, config('services.kedaton.employee_opd_code_fields', []));
        $name = $this->firstScalar($data, config('services.kedaton.employee_opd_name_fields', []));
        if (! $code) return null;
        $opd = Opd::withTrashed()->firstOrCreate(
            ['code' => trim($code)],
            ['name' => trim($name ?: $code), 'is_active' => true, 'synced_at' => now()],
        );
        if ($opd->trashed()) $opd->restore();
        if ($name) $opd->update(['name' => trim($name), 'is_active' => true, 'synced_at' => now()]);
        \App\Models\Identity\User::whereKey($userId)->update(['opd_id' => $opd->id]);
        return $opd;
    }

    private function firstScalar(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') return trim((string) $value);
        }
        foreach (['data', 'user', 'pegawai', 'employee'] as $nested) {
            if (is_array($data[$nested] ?? null)) {
                $found = $this->firstScalar($data[$nested], $keys);
                if ($found !== null) return $found;
            }
        }
        return null;
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
            if (! is_scalar($candidate)) {
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
