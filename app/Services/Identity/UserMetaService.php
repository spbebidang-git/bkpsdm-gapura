<?php

namespace App\Services\Identity;

use App\Models\Identity\UserMeta;
use App\Repositories\Identity\Contracts\UserMetaRepositoryInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class UserMetaService
{
    public function __construct(private readonly UserMetaRepositoryInterface $metas) {}

    public function get(string $userId, string $key): mixed
    {
        $meta = $this->metas->findByUserAndKey($userId, $key);
        if (! $meta) return false;
        if ($key === 'nip') {
            try { return Crypt::decryptString($meta->value); }
            catch (DecryptException) { return $meta->value; }
        }
        return $meta->value;
    }

    public function findUserByNip(string $nip): ?UserMeta
    {
        $hash = hash_hmac('sha256', $nip, config('app.key'));
        $metaHash = $this->metas->findWithUserByKeyAndValue('nip_hash', $hash);
        if ($metaHash) return $this->metas->findWithUserByUserAndKey($metaHash->user_id, 'nip') ?? $metaHash;

        foreach ($this->metas->getWithUserByKey('nip') as $meta) {
            try {
                if (Crypt::decryptString($meta->value) === $nip) return $meta;
            } catch (DecryptException) {
                if ($meta->value === $nip) return $meta;
            }
        }
        return null;
    }

    public function put(string $userId, string $key, string $value): UserMeta
    {
        if ($key === 'nip') {
            $hash = hash_hmac('sha256', $value, config('app.key'));
            $this->metas->put($userId, 'nip_hash', $hash);
            $value = Crypt::encryptString($value);
        }
        return $this->metas->put($userId, $key, $value);
    }
}
