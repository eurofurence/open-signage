<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->boolean('managed')->default(false)->after('artist');
            $table->string('external_url')->nullable()->after('managed');
            $table->string('external_checksum')->nullable()->after('external_url');

            $table->unique('external_url');
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropUnique(['external_url']);
            $table->dropColumn(['managed', 'external_url', 'external_checksum']);
        });
    }
};
