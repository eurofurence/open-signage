<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignIdFor(Project::class)->nullable()->after('venue_name')->constrained();
            $table->integer('external_id')->nullable()->after('project_id');
            $table->unique(['project_id', 'external_id']);
            $table->dropColumn('external_name');
        });

        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->foreignIdFor(Project::class)->nullable()->after('message')->constrained();
            $table->integer('external_id')->nullable()->after('project_id');
            $table->unique(['project_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('external_name')->nullable()->after('venue_name');
            $table->unique('external_name');
            $table->dropConstrainedForeignIdFor(Project::class);
            $table->dropColumn('external_id');
        });

        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Project::class);
            $table->dropColumn('external_id');
        });
    }
};
