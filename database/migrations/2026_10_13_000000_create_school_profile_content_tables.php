<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_profile_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('tagline');
            $table->text('address');
            $table->string('phone');
            $table->string('whatsapp');
            $table->string('email');
            $table->string('latitude', 40);
            $table->string('longitude', 40);
            $table->text('map_link');
            $table->string('service_hours');
            $table->longText('welcome_message');
            $table->longText('vision');
            $table->longText('mission');
            $table->longText('history');
            $table->longText('smp_description');
            $table->longText('sma_description');
            $table->json('social_links');
            $table->timestamps();
        });

        $placeholder = '[Isi dari panel admin]';
        DB::table('school_profile_settings')->insert([
            'id' => 1,
            'name' => $placeholder,
            'tagline' => $placeholder,
            'address' => $placeholder,
            'phone' => $placeholder,
            'whatsapp' => $placeholder,
            'email' => $placeholder,
            'latitude' => $placeholder,
            'longitude' => $placeholder,
            'map_link' => $placeholder,
            'service_hours' => $placeholder,
            'welcome_message' => $placeholder,
            'vision' => $placeholder,
            'mission' => $placeholder,
            'history' => $placeholder,
            'smp_description' => $placeholder,
            'sma_description' => $placeholder,
            'social_links' => json_encode([
                'instagram' => $placeholder,
                'facebook' => $placeholder,
                'youtube' => $placeholder,
                'tiktok' => $placeholder,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('school_facilities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('school_level', 3)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('school_galleries', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('photo_path');
            $table->string('school_level', 3)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_galleries');
        Schema::dropIfExists('school_facilities');
        Schema::dropIfExists('school_profile_settings');
    }
};
