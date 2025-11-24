<?php

namespace Cierra\Auth\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class GenerateOAuthClientCommand extends Command
{
    public $signature = 'cierra-auth:generate-oauth-client 
                        {name : The name of the OAuth client}
                        {--redirect=* : Redirect URIs for the OAuth client}';

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

        // Get redirect URIs
        $redirectUris = $this->option('redirect');

        // If no redirect URIs provided, ask for them
        if (empty($redirectUris)) {
            $this->info('No redirect URIs provided. Please enter at least one redirect URI.');
            $redirectUri = $this->ask('Enter redirect URI (e.g., https://example.com/oauth/callback)');

            if (! $redirectUri) {
                $this->error('At least one redirect URI is required.');

                return self::FAILURE;
            }

            $redirectUris = [$redirectUri];

            // Ask if they want to add more
            while ($this->confirm('Add another redirect URI?', false)) {
                $redirectUri = $this->ask('Enter redirect URI');
                if ($redirectUri) {
                    $redirectUris[] = $redirectUri;
                }
            }
        }

        // Make the API request
        $this->info('Creating temporary OAuth client...');

        try {
            $response = Http::withHeaders([
                'Authorization' => "X-Client-Enrollment-Secret: {$enrollmentSecret}",
                'Accept' => 'application/json',
            ])->post("{$host}/api/oauth/clients/enroll", [
                'name' => $clientName,
                'redirect' => $redirectUris,
            ]);

            if (! $response->successful()) {
                $this->error('Failed to create OAuth client.');
                $this->error('Response: '.$response->body());

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

            // Ask if they want to update the .env file
            if ($this->confirm('Would you like to update your .env file with these credentials?', true)) {
                $this->updateEnvFile($data['client_id'], $data['client_secret']);
            } else {
                $this->warn('⚠ IMPORTANT: The client secret is shown only once. Make sure to save it securely!');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Update the .env file with the new OAuth credentials
     */
    protected function updateEnvFile(string $clientId, string $clientSecret): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->error('.env file not found.');

            return;
        }

        $envContent = File::get($envPath);

        // Update or add CLIENT_ID
        if (preg_match('/^CIERRA_AUTH_CLIENT_ID=.*/m', $envContent)) {
            $envContent = preg_replace(
                '/^CIERRA_AUTH_CLIENT_ID=.*/m',
                "CIERRA_AUTH_CLIENT_ID={$clientId}",
                $envContent
            );
        } else {
            $envContent .= "\nCIERRA_AUTH_CLIENT_ID={$clientId}";
        }

        // Update or add CLIENT_SECRET
        if (preg_match('/^CIERRA_AUTH_CLIENT_SECRET=.*/m', $envContent)) {
            $envContent = preg_replace(
                '/^CIERRA_AUTH_CLIENT_SECRET=.*/m',
                "CIERRA_AUTH_CLIENT_SECRET={$clientSecret}",
                $envContent
            );
        } else {
            $envContent .= "\nCIERRA_AUTH_CLIENT_SECRET={$clientSecret}";
        }

        File::put($envPath, $envContent);

        $this->info('✓ .env file updated successfully!');
        $this->warn('⚠ Remember to restart your application to apply the new credentials.');
    }
}

