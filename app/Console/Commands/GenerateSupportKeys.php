<?php

namespace App\Console\Commands;

use App\Services\Support\UserContextMinter;
use Illuminate\Console\Command;

/**
 * Makes the two secrets that connect this app to the support assistant service, and prints them once. Nothing is written to disk:
 * copy each line into the right .env. The private signing key stays here; only the public key goes to the service.
 */
class GenerateSupportKeys extends Command
{
    protected $signature = 'support:generate-keys';

    protected $description = 'Generate the request-signing secret and the user-context key pair for the support assistant service';

    public function handle(): int
    {
        $secretKey = sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair());
        $hmacSecret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $pem = str_replace("\n", '\n', trim(UserContextMinter::publicKeyPem($secretKey)));

        $this->line('Put these in ModelHub\'s .env:');
        $this->newLine();
        $this->line('SUPPORT_HMAC_SECRET='.$hmacSecret);
        $this->line('SUPPORT_CONTEXT_PRIVATE_KEY='.base64_encode($secretKey));
        $this->newLine();
        $this->line('Put these in the support service\'s .env (modelhub-support):');
        $this->newLine();
        $this->line('APP_HMAC_KEYS=current:'.$hmacSecret);
        $this->line('APP_USER_CONTEXT_PUBLIC_KEY='.$pem);
        $this->newLine();
        $this->warn('These are shown once and not saved. The private key must never leave this app.');

        return self::SUCCESS;
    }
}
