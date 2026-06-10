<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing-site CMS — CENTRAL, super-admin managed.
 *
 *  cms_pages    : landing / shop-type / FBR / custom pages (+ SEO).
 *  cms_sections : ordered blocks on a page, each show/hide by global + country.
 *  blog_posts   : blog with per-post SEO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('type', 30)->default('custom'); // landing|shop_type|fbr|custom
            $table->string('shop_type', 60)->nullable();    // for type=shop_type
            $table->boolean('show_in_menu')->default(false);
            $table->unsignedInteger('menu_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('cms_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id')->index();
            $table->string('key')->nullable();
            $table->string('type', 40)->default('html');   // hero|features|pricing|html|cta…
            $table->longText('content')->nullable();        // JSON or HTML
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('visible_global')->default(true);
            $table->json('visible_countries')->nullable();  // null = all countries
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('cover_image')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('author')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('cms_sections');
        Schema::dropIfExists('cms_pages');
    }
};
