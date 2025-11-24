<?php

use Illuminate\Support\Facades\Artisan;

it('registers the generate oauth client command', function () {
    // Get all registered commands
    $commands = array_keys(Artisan::all());
    
    // Check if our command is registered
    expect($commands)->toContain('cierra-auth:generate-oauth-client');
});

it('registers the cierra auth command', function () {
    // Get all registered commands
    $commands = array_keys(Artisan::all());
    
    // Check if our command is registered
    expect($commands)->toContain('cierra-auth');
});
