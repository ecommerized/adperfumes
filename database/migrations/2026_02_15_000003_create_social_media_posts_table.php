<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_posts', function (Blueprint $table) {
            $table->id();

            // Post type
            $table->enum('type', ['product_promo', 'offer', 'brand_story', 'custom'])->default('product_promo');

            // Content
            $table->text('caption');
            $table->text('hashtags')->nullable();
            $table->string('image_path', 500)->nullable();

            // Relationships (nullable FKs)
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete();

            // Status workflow
            $table->enum('status', ['draft', 'scheduled', 'posting', 'published', 'failed'])->default('draft');

            // Scheduling & publishing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // Facebook response tracking
            $table->string('facebook_post_id')->nullable();
            $table->text('error_message')->nullable();

            // Flexible metadata
            $table->json('meta_data')->nullable();

            // Tracking
            $table->string('created_by')->default('admin');
            $table->string('source')->default('manual');

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('type');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_posts');
    }
};
