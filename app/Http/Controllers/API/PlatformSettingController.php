<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Super-admin global billing settings: long-term discount, add-on rates and FBR
 * price. Each value can be set globally or overridden per-country
 * ("{key}_{COUNTRY}"); reads prefer the country override, then the global value.
 */
class PlatformSettingController extends AppBaseController
{
    private array $keys = [
        'long_term_discount_percent',
        'long_term_discount_min_months',
        'addon_shop_rate',
        'addon_user_rate',
        'addon_product_rate',
        'fbr_monthly_price',
        'fbr_yearly_price',
    ];

    public function show(Request $request): JsonResponse
    {
        // When a country is given, return that country's effective values
        // (override falling back to global). Otherwise return the global values.
        $country = $request->get('country');

        $read = fn (string $key, $default) => $country
            ? PlatformSetting::getForCountry($key, $country, $default)
            : (PlatformSetting::get($key) ?? $default);

        $settings = [
            'country'                       => $country ?: '',
            'long_term_discount_percent'    => (float) $read('long_term_discount_percent', 0),
            'long_term_discount_min_months' => (int) $read('long_term_discount_min_months', 12),
            'addon_shop_rate'               => (float) $read('addon_shop_rate', 0),
            'addon_user_rate'               => (float) $read('addon_user_rate', 0),
            'addon_product_rate'            => (float) $read('addon_product_rate', 0),
            'fbr_monthly_price'             => (float) $read('fbr_monthly_price', 0),
            'fbr_yearly_price'              => (float) $read('fbr_yearly_price', 0),
        ];

        return $this->sendResponse($settings, 'Platform settings retrieved successfully.');
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country'                       => 'nullable|string|max:5',
            'long_term_discount_percent'    => 'nullable|numeric|min:0|max:100',
            'long_term_discount_min_months' => 'nullable|integer|min:1|max:60',
            'addon_shop_rate'               => 'nullable|numeric|min:0',
            'addon_user_rate'               => 'nullable|numeric|min:0',
            'addon_product_rate'            => 'nullable|numeric|min:0',
            'fbr_monthly_price'             => 'nullable|numeric|min:0',
            'fbr_yearly_price'              => 'nullable|numeric|min:0',
        ]);

        // Empty country = global keys; a country writes "{key}_{COUNTRY}".
        $suffix = ! empty($data['country']) ? '_' . strtoupper($data['country']) : '';

        foreach ($this->keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                PlatformSetting::set($key . $suffix, $data[$key]);
            }
        }

        return $this->show($request);
    }

    /**
     * Default global checkout channel: a single hosted "payment link" shown to
     * every country.
     */
    public static function defaultGlobalChannels(): array
    {
        return [
            ['key' => 'link', 'label' => 'Online Payment Link', 'type' => 'link', 'account' => '', 'url' => '', 'instructions' => 'Open the secure link to complete your payment.'],
        ];
    }

    /**
     * Default Pakistan checkout channels: mobile wallets + local banks.
     */
    public static function defaultPkChannels(): array
    {
        return [
            ['key' => 'jazzcash', 'label' => 'JazzCash',  'type' => 'wallet', 'account' => '', 'url' => '', 'instructions' => 'Send to our JazzCash number, then enter the TID.'],
            ['key' => 'easypaisa','label' => 'Easypaisa',  'type' => 'wallet', 'account' => '', 'url' => '', 'instructions' => 'Send to our Easypaisa number, then enter the TID.'],
            ['key' => 'hbl',      'label' => 'HBL Bank',    'type' => 'bank',   'account' => '', 'url' => '', 'instructions' => 'Bank transfer to our HBL account, then enter the reference.'],
            ['key' => 'meezan',   'label' => 'Meezan Bank', 'type' => 'bank',   'account' => '', 'url' => '', 'instructions' => 'Bank transfer to our Meezan account, then enter the reference.'],
            ['key' => 'ubl',      'label' => 'UBL Bank',    'type' => 'bank',   'account' => '', 'url' => '', 'instructions' => 'Bank transfer to our UBL account, then enter the reference.'],
            ['key' => 'other',    'label' => 'Other Bank/Wallet', 'type' => 'bank', 'account' => '', 'url' => '', 'instructions' => 'Transfer and enter the reference / TID.'],
        ];
    }

    /**
     * Resolve the configured checkout channels for a country (falls back to the
     * built-in defaults: PK locals for Pakistan, the hosted link otherwise).
     */
    public static function channelsFor(?string $country): array
    {
        $raw = PlatformSetting::getForCountry('billing.checkout.channels', $country, null);
        $arr = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
        if (is_array($arr) && count($arr)) {
            return array_values($arr);
        }
        return ($country && strtoupper($country) === 'PK')
            ? self::defaultPkChannels()
            : self::defaultGlobalChannels();
    }

    /**
     * Which subscription payment methods are enabled platform-wide, plus the
     * checkout channels for the requested country.
     */
    public function billingMethods(Request $request): JsonResponse
    {
        $country = $request->get('country');

        return $this->sendResponse([
            'country'          => $country ?: '',
            // Request (manual proof upload) is enabled by default; online
            // checkout is opt-in.
            'request_enabled'  => (PlatformSetting::get('billing.method.request') ?? '1') === '1',
            'checkout_enabled' => (PlatformSetting::get('billing.method.checkout') ?? '0') === '1',
            'channels'         => self::channelsFor($country),
        ], 'Billing methods retrieved successfully.');
    }

    public function updateBillingMethods(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country'                 => 'nullable|string|max:5',
            'request_enabled'         => 'nullable|boolean',
            'checkout_enabled'        => 'nullable|boolean',
            'channels'                => 'nullable|array',
            'channels.*.key'          => 'required|string|max:40',
            'channels.*.label'        => 'required|string|max:80',
            'channels.*.type'         => 'nullable|in:link,wallet,bank',
            'channels.*.account'      => 'nullable|string|max:191',
            'channels.*.url'          => 'nullable|string|max:255',
            'channels.*.instructions' => 'nullable|string|max:500',
        ]);

        // Method availability is platform-wide (not country scoped).
        if (array_key_exists('request_enabled', $data)) {
            PlatformSetting::set('billing.method.request', $data['request_enabled'] ? '1' : '0');
        }
        if (array_key_exists('checkout_enabled', $data)) {
            PlatformSetting::set('billing.method.checkout', $data['checkout_enabled'] ? '1' : '0');
        }

        // Channels can be tuned per country (PK gets local wallets/banks).
        if (array_key_exists('channels', $data)) {
            $suffix = ! empty($data['country']) ? '_' . strtoupper($data['country']) : '';
            PlatformSetting::set('billing.checkout.channels' . $suffix, json_encode(array_values($data['channels'])));
        }

        return $this->billingMethods($request);
    }
}
