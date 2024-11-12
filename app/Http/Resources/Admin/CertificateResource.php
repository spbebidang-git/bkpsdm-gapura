<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray($request): array
    {
        dd(321);
        return [
            'nama_kegiatan' => $this->nama_kegiatan
        ];
    }
}
