<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_posts')) { return; }

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image', 500)->nullable();
            $table->string('author')->default('AD Perfumes');

            // Status workflow
            $table->enum('status', ['draft', 'pending_review', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();

            // AI generation tracking
            $table->string('topic_source')->default('ai');
            $table->integer('seo_score')->default(0);
            $table->json('meta_data')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('published_at');
            $table->index('seo_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
