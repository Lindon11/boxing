<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('belts', function (Blueprint $table) {
            $table->string('logo_url', 2048)->nullable()->after('active');
        });
    }
    public function down(): void {
        Schema::table('belts', function (Blueprint $table) {
            $table->dropColumn('logo_url');
        });
    }
};
