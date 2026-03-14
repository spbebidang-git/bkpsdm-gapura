<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $nip = get_user_meta($this->id, 'nip');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nip' => $nip ? $this->maskNip($nip) : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Mask NIP for privacy
     */
    private function maskNip(string $nip): string
    {
        $length = strlen($nip);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return substr($nip, 0, 4) . str_repeat('*', $length - 6) . substr($nip, -2);
    }
}
