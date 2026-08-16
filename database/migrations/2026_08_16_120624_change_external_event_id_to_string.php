<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->string('external_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Discard any external_id that would not survive the cast back to an integer,
        // either because it is not numeric at all or because it exceeds the INT range.
        DB::table('schedule_entries')
            ->whereNotNull('external_id')
            ->where(function ($query) {
                $query->whereRaw("external_id NOT REGEXP '^-?[0-9]+\$'")
                    ->orWhereRaw('CAST(external_id AS SIGNED) NOT BETWEEN -2147483648 AND 2147483647');
            })
            ->update(['external_id' => null]);

        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->integer('external_id')->nullable()->change();
            $table->index('external_id');
        });
    }
};
