<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fighters', 'source_url')) {
            Schema::table('fighters', function (Blueprint $table) {
                $table->text('source_url')->nullable()->after('photo_url');
            });
        }

        if (! Schema::hasColumn('events', 'source_url')) {
            Schema::table('events', function (Blueprint $table) {
                $table->text('source_url')->nullable()->after('ticket_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fighters', 'source_url')) {
            Schema::table('fighters', function (Blueprint $table) {
                $table->dropColumn('source_url');
            });
        }

        if (Schema::hasColumn('events', 'source_url')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('source_url');
            });
        }
    }
};
