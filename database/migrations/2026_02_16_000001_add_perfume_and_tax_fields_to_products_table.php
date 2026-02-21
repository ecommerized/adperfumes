<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'price_excluding_tax')) { return; }

        Schema::table('products', function (Blueprint $table) {
            // Tax-inclusive pricing (UAE VAT = 5%)
            $table->decimal('price_excluding_tax', 10, 2)->nullable()->after('price');
            $table->decimal('tax_amount', 10, 2)->nullable()->after('price_excluding_tax');
            $table->decimal('tax_rate', 5, 2)->default(5.00)->after('tax_amount');
            $table->decimal('compare_at_price', 10, 2)->nullable()->after('tax_rate');

            // Perfume-specific fields
            $table->string('sku')->nullable()->after('compare_at_price');
            $table->text('short_description')->nullable()->after('description');
            $table->string('perfume_house')->nullable()->after('short_description');
            $table->string('country_of_origin')->nullable()->after('perfume_house');
            $table->string('concentration')->nullable()->after('country_of_origin');
            $table->string('gender')->nullable()->after('concentration');
            $table->integer('volume_ml')->nullable()->after('gender');
            $table->string('volume_display')->nullable()->after('volume_ml');

            // Fragrance characteristics
            $table->string('scent_family')->nullable();
            $table->string('season')->nullable();
            $table->string('occasion')->nullable();
            $table->string('longevity')->nullable();
            $table->string('sillage')->nullable();
            $table->integer('longevity_hours')->nullable();
            $table->year('launch_year')->nullable();

            // Extended catalog fields
            $table->integer('low_stock_threshold')->default(5);
            $table->json('gallery_images')->nullable();
            $table->string('video_url')->nullable();

            // Feature flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_tester')->default(false);
            $table->boolean('is_authentic_guaranteed')->default(true);

            // Shipping attributes
            $table->integer('weight_grams')->nullable();
            $table->boolean('is_flammable')->default(true);
            $table->boolean('available_for_international_shipping')->default(false);

            // Indexes
            $table->index('concentration');
            $table->index('gender');
            $table->index('scent_family');
        });

        // Backfill tax fields for existing products (5% VAT)
        DB::statement('UPDATE products SET price_excluding_tax = ROUND(price / 1.05, 2), tax_amount = ROUND(price - (price / 1.05), 2) WHERE price_excluding_tax IS NULL AND price > 0');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['concentration']);
            $table->dropIndex(['gender']);
            $table->dropIndex(['scent_family']);

            $table->dropColumn([
                'price_excluding_tax', 'tax_amount', 'tax_rate', 'compare_at_price',
                'sku', 'short_description', 'perfume_house', 'country_of_origin',
                'concentration', 'gender', 'volume_ml', 'volume_display',
                'scent_family', 'season', 'occasion', 'longevity', 'sillage',
                'longevity_hours', 'launch_year',
                'low_stock_threshold', 'gallery_images', 'video_url',
                'is_featured', 'is_bestseller', 'is_exclusive', 'is_tester', 'is_authentic_guaranteed',
                'weight_grams', 'is_flammable', 'available_for_international_shipping',
            ]);
        });
    }
};
