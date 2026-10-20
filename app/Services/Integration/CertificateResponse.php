<?php

namespace App\Services\Integration;

use Illuminate\Http\Client\Response;
use RuntimeException;

class CertificateResponse
{
    public static function data(Response $response): array
    {
        if (! $response->successful()) {
            throw new RuntimeException('Prestige gagal merespons (HTTP '.$response->status().').');
        }
        $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            throw new RuntimeException('Respons sertifikat Prestige bukan array JSON.');
        }
        foreach (['success', 'status'] as $key) {
            if (array_key_exists($key, $data) && in_array($data[$key], [false, 0, '0', 'false', 'error', 'failed', 'failure'], true)) {
                throw new RuntimeException('Prestige menyatakan pengiriman atau pengambilan sertifikat gagal.');
            }
        }
        if (! empty($data['error']) || ! empty($data['errors'])) {
            throw new RuntimeException('Prestige mengembalikan error sertifikat.');
        }

        return $data;
    }

    public static function accepted(Response $response): bool
    {
        try {
            $data = self::data($response);
            if (($data['message'] ?? null) !== 'Berhasil Disimpan'
                || ! is_scalar($data['kode'] ?? null) || ! ctype_digit((string) $data['kode'])
                || (int) $data['kode'] <= 0 || ! is_array($data['nip_tidak_ditemukan'] ?? null)
                || ! array_is_list($data['nip_tidak_ditemukan'])) {
                return false;
            }
            foreach ($data['nip_tidak_ditemukan'] as $nip) {
                if ((! is_string($nip) && ! is_int($nip)) || ! normalize_nip($nip)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
