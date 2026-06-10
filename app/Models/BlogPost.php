<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * A blog post with per-post SEO (central).
 */
class BlogPost extends Model
{
    use CentralConnection;

    protected $table = 'blog_posts';

    protected $fillable = [
        'slug', 'title', 'cover_image', 'excerpt', 'content', 'author',
        'seo_title', 'seo_description', 'seo_keywords', 'status', 'published_at',
    ];

    protected $casts = [
        'status'       => 'boolean',
        'published_at' => 'datetime',
    ];
}
