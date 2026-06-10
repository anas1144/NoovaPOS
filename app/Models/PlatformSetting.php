<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * Central key/value platform settings (super-admin controlled).
 *
 * Known keys:
 *   long_term_discount_percent   - % off when buying >= threshold months
 *   long_term_discount_min_months- threshold in months (default 12)
 *   addon_shop_rate / addon_user_rate / addon_product_rate - per-unit add-on price
 */
class PlatformSetting extends Model
{
    use CentralConnection;

    protected $table = 'platform_settings';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $row = static::query()->where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    public static function getFloat(string $key, float $default = 0): float
    {
        $v = static::get($key);
        return is_numeric($v) ? (float) $v : $default;
    }

    /**
     * Country-aware value: prefers a per-country override ("{key}_{COUNTRY}")
     * and falls back to the global value, then the default.
     */
    public static function getForCountry(string $key, ?string $country, $default = null)
    {
        if ($country) {
            $override = static::get($key . '_' . strtoupper($country));
            if ($override !== null && $override !== '') {
                return $override;
            }
        }
        $global = static::get($key);
        return ($global !== null && $global !== '') ? $global : $default;
    }

    public static function getFloatForCountry(string $key, ?string $country, float $default = 0): float
    {
        $v = static::getForCountry($key, $country);
        return is_numeric($v) ? (float) $v : $default;
    }

    public static function set(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}
