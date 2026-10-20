<?php

namespace App\Http\Controllers\Participant;

use Inertia\Inertia;

class ProfileController
{
    public function index()
    {
        $user = request()->user()->loadMissing(['metas', 'opd', 'employeeStatus']);
        $text = static function ($value): ?string {
            if (! is_scalar($value)) {
                return null;
            }
            $value = trim((string) $value);

            return $value !== '' && $value !== '-' ? $value : null;
        };
        $meta = fn (string $key) => $text($user->metas->firstWhere('key', $key)?->value);
        $unitKerja = $text($user->opd?->name);
        if ($unitKerja === null) {
            foreach (config('services.kedaton.employee_opd_name_fields', []) as $key) {
                if (($unitKerja = $meta($key)) !== null) {
                    break;
                }
            }
        }

        return Inertia::render('Participant/Profile/Index', [
            'profile' => [
                'name' => $text($user->name),
                'email' => $this->maskEmail($text($user->email)),
                'nip' => $this->mask($text($user->nip), 3),
                'unit_kerja' => $unitKerja,
                'status_pegawai' => $text($user->employeeStatus?->title) ?? $meta('status_pegawai'),
                'jabatan' => $meta('nama_jabatan'),
            ],
        ]);
    }

    private function mask(?string $value, int $visible): ?string
    {
        if ($value === null) {
            return null;
        }
        if (mb_strlen($value) <= $visible * 2) {
            return 'xxxx';
        }

        return mb_substr($value, 0, $visible).'xxxx'.mb_substr($value, -$visible);
    }

    private function maskEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }
        $separator = mb_strrpos($email, '@');
        if ($separator === false) {
            return $this->mask($email, 2);
        }

        return $this->mask(mb_substr($email, 0, $separator), 2).mb_substr($email, $separator);
    }
}
