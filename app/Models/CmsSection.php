<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An ordered block on a CMS page, with global + country visibility (central).
 */
class CmsSection extends Model
{
    use CentralConnection;

    protected $table = 'cms_sections';

    protected $fillable = [
        'page_id', 'key', 'type', 'content', 'sort_order',
        'visible_global', 'visible_countries', 'status',
    ];

    protected $casts = [
        'sort_order'        => 'integer',
        'visible_global'    => 'boolean',
        'visible_countries' => 'array',
        'status'            => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    /**
     * Visible for the given country? Hidden if globally off; otherwise visible
     * when no country restriction or the country is allowed.
     */
    public function isVisibleFor(?string $country): bool
    {
        if (! $this->status || ! $this->visible_global) {
            return false;
        }
        $countries = $this->visible_countries;
        if (empty($countries)) {
            return true;
        }
        return $country && in_array(strtoupper($country), array_map('strtoupper', $countries), true);
    }
}
