<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:token {email? : The email of the user to generate token for} 
                            {--name=kominfo : The name of the token}
                            {--list : List tokens for the user}
                            {--revoke= : Revoke a specific token by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage API tokens for users (for Kominfo/clients)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if (!$email) {
            $email = $this->ask('Enter the user email to manage tokens for');
        }

        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return Command::FAILURE;
        }

        if ($this->option('list')) {
            $this->info("Tokens for {$user->name} ({$user->email}):");
            $tokens = $user->tokens;
            if ($tokens->isEmpty()) {
                $this->warn("No tokens found.");
            } else {
                $this->table(
                    ['ID', 'Name', 'Abilities', 'Last Used', 'Created At'],
                    $tokens->map(function ($token) {
                        return [
                            $token->id,
                            $token->name,
                            implode(', ', $token->abilities ?? []),
                            $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never',
                            $token->created_at->format('Y-m-d H:i:s'),
                        ];
                    })
                );
            }
            return Command::SUCCESS;
        }

        if ($tokenId = $this->option('revoke')) {
            $token = $user->tokens()->where('id', $tokenId)->first();
            if ($token) {
                $token->delete();
                $this->info("Token ID {$tokenId} revoked successfully.");
            } else {
                $this->error("Token ID {$tokenId} not found for this user.");
            }
            return Command::SUCCESS;
        }

        // Generate Token
        $tokenName = $this->option('name');
        $token = $user->createToken($tokenName);

        $this->info("API Token generated successfully for {$user->name}!");
        $this->warn("Please copy this token now. You won't be able to see it again:");
        $this->line('');
        $this->info($token->plainTextToken);
        $this->line('');

        return Command::SUCCESS;
    }
}
