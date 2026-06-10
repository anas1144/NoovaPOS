<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A marketing-site CMS page (central).
 */
class CmsPage extends Model
{
    use CentralConnection;

    protected $table = 'cms_pages';

    protected $fillable = [
        'slug', 'title', 'type', 'shop_type', 'show_in_menu', 'menu_order',
        'seo_title', 'seo_description', 'seo_keywords', 'status', 'is_system',
    ];

    protected $casts = [
        'show_in_menu' => 'boolean',
        'menu_order'   => 'integer',
        'status'       => 'boolean',
        'is_system'    => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class, 'page_id')->orderBy('sort_order');
    }
}
