<?php

namespace Cierra\Auth\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class GenerateOAuthClientCommand extends Command
{
    public $signature = 'cierra-auth:generate-oauth-client 
                        {name : The name of the OAuth client}';

    public $description = 'Generate a temporary OAuth client for development/preview environments';

    public function handle(): int
    {
        // Get the enrollment secret from config
        $enrollmentSecret = config('cierra-auth-package.client_enrollment_secret');

        if (! $enrollmentSecret) {
            $this->error('CLIENT_ENROLLMENT_SECRET is not set in your .env file.');
            $this->info('Please add CIERRA_AUTH_CLIENT_ENROLLMENT_SECRET to your .env file.');

            return self::FAILURE;
        }

        // Get the auth host
        $host = config('cierra-auth-package.host');

        // Get client name from argument
        $clientName = $this->argument('name');

        // Automatically generate redirect URI from app URL
        $appUrl = rtrim(config('app.url'), '/');
        $redirectUri = $appUrl.'/cierra-auth/callback';
        $redirectUris = [$redirectUri];

        $this->info("Using redirect URI: {$redirectUri}");

        // Make the API request
        $this->info('Creating temporary OAuth client...');

        try {
            $response = Http::withHeaders([
                'X-Client-Enrollment-Secret' => "{$enrollmentSecret}",
                'Accept' => 'application/json',
            ])->post("{$host}/api/oauth/clients/enroll", [
                'name' => $clientName,
                'redirect' => $redirectUris,
            ]);

            if (! $response->successful()) {
                $this->error('Failed to create OAuth client.');
                $this->error('Response: '.$response->body());
                $this->error('Params used: '.json_encode([
                    'name' => $clientName,
                    'redirect' => $redirectUris,
                ]));
                $this->error('Headers used: '.json_encode([
                    'Authorization' => "X-Client-Enrollment-Secret: {$enrollmentSecret}",
                    'Accept' => 'application/json',
                ]));

                return self::FAILURE;
            }

            $data = $response->json();

            // Display the credentials
            $this->info('✓ OAuth client created successfully!');
            $this->newLine();
            $this->line('<fg=yellow>Client Credentials:</>');
            $this->line("Client ID:     {$data['client_id']}");
            $this->line("Client Secret: {$data['client_secret']}");
            $this->line("Expires At:    {$data['expires_at']}");
            $this->newLine();

            $this->writeToStorageFile($data['client_id'], $data['client_secret'], $data['expires_at']);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Write the OAuth credentials to a JSON file in the storage directory
     */
    protected function writeToStorageFile(string $clientId, string $clientSecret, string $expiresAt): void
    {
        $storagePath = storage_path('cierra-auth-oauth.json');

        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'expires_at' => $expiresAt,
        ];

        File::put($storagePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("✓ OAuth credentials written to {$storagePath}");
    }
}
