<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Management\UserMeta;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;

class SyncNipHash extends Command
{
    protected $signature = 'app:sync-nip-hash';

    protected $description = 'Generate and synchronize NIP hash for extremely fast searching on existing user_metas.';

    public function handle(): void
    {
        $this->info('Starting NIP hash synchronization...');

        $metas = UserMeta::where('key', 'nip')->get();
        $this->info('Found ' . $metas->count() . ' NIP records.');

        $processed = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($metas as $meta) {
                $nip = null;

                try {
                    $nip = Crypt::decryptString($meta->value);
                } catch (DecryptException $e) {
                    $nip = $meta->value;
                }

                if ($nip) {
                    $nip = trim($nip);
                    $hashValue = hash('sha256', $nip);

                    $existingHash = UserMeta::where('user_id', $meta->user_id)
                        ->where('key', 'nip_hash')
                        ->first();

                    if ($existingHash) {
                        $existingHash->update(['value' => $hashValue]);
                    } else {
                        UserMeta::create([
                            'user_id' => $meta->user_id,
                            'key' => 'nip_hash',
                            'value' => $hashValue,
                        ]);
                    }

                    $processed++;
                } else {
                    $failed++;
                }
            }

            DB::commit();
            $this->info("Successfully processed {$processed} records. Failed: {$failed} records.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to synchronize NIP hashes. Rolled back. Error: ' . $e->getMessage());
        }
    }
}
