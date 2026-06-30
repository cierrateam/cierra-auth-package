<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the columns the package syncs from admin.cierra.ai's /api/user:
     *   - position       : job title (optionally sourced from Entra ID upstream)
     *   - mail_signature  : the user's default HTML mail signature
     *
     * Both are additive and nullable so consuming apps on older versions
     * keep working untouched.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable();
            }

            if (! Schema::hasColumn('users', 'mail_signature')) {
                $table->longText('mail_signature')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['position', 'mail_signature'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
