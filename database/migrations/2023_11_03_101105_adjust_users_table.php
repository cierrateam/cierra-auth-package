<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //create first_name column if not exists

            $cols = [
                [
                    'name' => 'first_name',
                ],
                [
                    'name' => 'last_name',
                ],
                [
                    'name' => 'email',
                    'unique' => true,
                ],
                [
                    'name' => 'token',
                    'type' => 'text'
                ],
                [
                    'name' => 'cierra_auth_id',
                    'type' => 'unsignedBigInteger',
                    'index' => true,
                ],
                [
                    'name' => 'token_expires_in',
                    'type' => 'timestamp',
                ],
                [
                    'name' => 'refresh_token',
                    'type' => 'text'
                ],
                [
                    'name' => 'email_verified_at',
                    'type' => 'timestamp',
                ],
            ];

            foreach ($cols as $col) {
                if (! Schema::hasColumn('users', $col['name'])) {
                    $table->{$col['type'] ?? 'string'}($col['name'])->nullable();
                }
                // if (isset($col['unique'])) {
                //     $table->unique($col['name']);
                // }
                // if (isset($col['index'])) {
                //     $table->index($col['name']);
                // }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
