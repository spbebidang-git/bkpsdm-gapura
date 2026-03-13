<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Management\UserMeta;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptNipCommand extends Command
{
    protected $signature = 'nip:encrypt';

    protected $description = 'Encrypt all existing NIPs and generate their blind indexes';

    public function handle(): void
    {
        $this->info('Starting NIP encryption process...');
        
        $userMetas = UserMeta::where('key', 'nip')->get();
        $count = 0;

        foreach ($userMetas as $meta) {
            $value = $meta->value;

            // Check if already encrypted to avoid double encryption
            $isEncrypted = false;
            try {
                Crypt::decryptString($value);
                $isEncrypted = true;
            } catch (DecryptException $e) {
                // Not encrypted yet, proceed
            }

            if (!$isEncrypted) {

                // Encrypt the original value
                $meta->value = Crypt::encryptString($value);
                $meta->save();

                $count++;
            }
        }

        $this->info("Successfully encrypted {$count} NIP records.");
    }
}
