<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 3)->unique();
            $table->string('flag_emoji')->nullable();
            $table->timestamps();
        });

        Schema::create('stances', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('weight_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('limit_pounds')->nullable();
            $table->decimal('limit_kg', 5, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('organisations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('abbreviation', 20)->unique();
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });

        Schema::create('promoters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('website_url')->nullable();
            $table->timestamps();
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city');
            $table->string('region')->nullable();
            $table->string('address')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();
        });

        Schema::create('result_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('abbreviation', 20)->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('fighters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('weight_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('display_name');
            $table->string('ring_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('residence')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('reach_cm')->nullable();
            $table->unsignedSmallInteger('wins')->default(0);
            $table->unsignedSmallInteger('losses')->default(0);
            $table->unsignedSmallInteger('draws')->default(0);
            $table->unsignedSmallInteger('no_contests')->default(0);
            $table->unsignedSmallInteger('knockouts')->default(0);
            $table->date('debut_date')->nullable();
            $table->boolean('active')->default(true);
            $table->string('photo_url')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->index(['display_name', 'slug']);
            $table->index(['country_id', 'weight_class_id']);
        });

        Schema::create('fighter_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fighter_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->timestamps();

            $table->unique(['fighter_id', 'alias']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promoter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->dateTime('event_date');
            $table->dateTime('ring_walks_at')->nullable();
            $table->string('status')->default('upcoming');
            $table->string('poster_url')->nullable();
            $table->string('hero_image_url')->nullable();
            $table->string('broadcast_notes')->nullable();
            $table->string('ticket_url')->nullable();
            $table->timestamps();

            $table->index(['status', 'event_date']);
        });

        Schema::create('fights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weight_class_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('red_corner_fighter_id')->constrained('fighters')->cascadeOnDelete();
            $table->foreignId('blue_corner_fighter_id')->constrained('fighters')->cascadeOnDelete();
            $table->foreignId('winner_fighter_id')->nullable()->constrained('fighters')->nullOnDelete();
            $table->foreignId('result_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('billing')->default('undercard');
            $table->unsignedSmallInteger('bout_order')->default(0);
            $table->unsignedSmallInteger('scheduled_rounds')->default(12);
            $table->unsignedSmallInteger('completed_rounds')->nullable();
            $table->boolean('is_title_fight')->default(false);
            $table->string('status')->default('scheduled');
            $table->dateTime('fight_date')->nullable();
            $table->string('result_notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'bout_order']);
            $table->index(['status', 'fight_date']);
        });

        Schema::create('belts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weight_class_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['organisation_id', 'weight_class_id']);
        });

        Schema::create('belt_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('belt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fighter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fight_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->date('reign_started_on');
            $table->date('reign_ended_on')->nullable();
            $table->string('status')->default('current');
            $table->string('result')->nullable();
            $table->timestamps();

            $table->index(['fighter_id', 'status']);
        });

        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weight_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fighter_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('rank');
            $table->unsignedInteger('points')->default(0);
            $table->date('ranked_on');
            $table->timestamps();

            $table->unique(['organisation_id', 'weight_class_id', 'fighter_id', 'ranked_on'], 'rankings_scope_unique');
            $table->index(['organisation_id', 'weight_class_id', 'rank']);
        });

        Schema::create('broadcasters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->timestamps();
        });

        Schema::create('event_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('broadcaster_id')->constrained()->cascadeOnDelete();
            $table->string('region')->default('Global');
            $table->string('platform')->nullable();
            $table->boolean('is_ppv')->default(false);
            $table->string('details')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'broadcaster_id', 'region']);
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable');
            $table->string('collection')->default('gallery');
            $table->string('title')->nullable();
            $table->string('url');
            $table->string('credit')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('referees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('fight_officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fight_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('judge_id')->nullable()->constrained()->nullOnDelete();
            $table->string('official_type');
            $table->timestamps();
        });

        Schema::create('scorecards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fight_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('winner_fighter_id')->nullable()->constrained('fighters')->nullOnDelete();
            $table->unsignedSmallInteger('red_score');
            $table->unsignedSmallInteger('blue_score');
            $table->timestamps();

            $table->unique(['fight_id', 'judge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorecards');
        Schema::dropIfExists('fight_officials');
        Schema::dropIfExists('judges');
        Schema::dropIfExists('referees');
        Schema::dropIfExists('media');
        Schema::dropIfExists('event_broadcasts');
        Schema::dropIfExists('broadcasters');
        Schema::dropIfExists('rankings');
        Schema::dropIfExists('belt_history');
        Schema::dropIfExists('belts');
        Schema::dropIfExists('fights');
        Schema::dropIfExists('events');
        Schema::dropIfExists('fighter_aliases');
        Schema::dropIfExists('fighters');
        Schema::dropIfExists('result_methods');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('promoters');
        Schema::dropIfExists('organisations');
        Schema::dropIfExists('weight_classes');
        Schema::dropIfExists('stances');
        Schema::dropIfExists('countries');
    }
};
