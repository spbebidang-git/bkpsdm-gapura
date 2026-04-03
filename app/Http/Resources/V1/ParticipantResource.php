<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $nip = get_user_meta($this->id, 'nip');

        return [
            'custom_unique_id' => Str::random(4),
            'data_date' => now()->format('Y-m-d H:i:s'),
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
