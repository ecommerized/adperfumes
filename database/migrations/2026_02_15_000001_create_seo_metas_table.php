<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship
            $table->morphs('seoable');

            // Core SEO fields
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 170)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('robots', 50)->default('index, follow');

            // Open Graph
            $table->string('og_title', 100)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->string('og_type', 50)->default('website');
            $table->string('og_image', 500)->nullable();

            // Twitter Card
            $table->string('twitter_card', 50)->default('summary_large_image');
            $table->string('twitter_title', 100)->nullable();
            $table->string('twitter_description', 200)->nullable();

            // Complex structured data as JSON
            $table->json('keywords')->nullable();
            $table->json('aeo_data')->nullable();
            $table->json('schema_markup')->nullable();
            $table->json('social_media')->nullable();
            $table->json('scoring')->nullable();
            $table->json('content_optimization')->nullable();

            // Protection flag
            $table->boolean('is_manually_edited')->default(false);

            // Tracking
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();

            // One SEO meta per model instance
            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
