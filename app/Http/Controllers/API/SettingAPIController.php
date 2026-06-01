<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Resources\SettingResource;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\State;
use App\Models\Store;
use App\Models\Warehouse;
use App\Repositories\SettingRepository;
use App\Services\TenantCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Class SettingAPIController
 */
class SettingAPIController extends AppBaseController
{
    /** @var SettingRepository */
    private $settingRepository;

    public function __construct(
        SettingRepository $productRepository,
        private readonly TenantCacheService $tenantCache
    ) {
        $this->settingRepository = $productRepository;
    }

    // ------------------------------------------------------------------
    // Cache key constants
    // ------------------------------------------------------------------
    private const CK_SETTINGS_FULL  = 'settings.full_response';
    private const CK_SETTINGS_FRONT = 'settings.front_response';
    private const CK_SETTINGS_POS   = 'settings.pos_response';
    private const CK_SETTINGS_DUAL  = 'settings.dual_screen';
    private const TTL_SETTINGS       = 3600; // 1 hour

    /** Flush every settings-related cache key for the current tenant. */
    private function flushSettingsCache(): void
    {
        foreach ([
            self::CK_SETTINGS_FULL,
            self::CK_SETTINGS_FRONT,
            self::CK_SETTINGS_POS,
            self::CK_SETTINGS_DUAL,
        ] as $key) {
            $this->tenantCache->forget($key);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $settings = $this->tenantCache->remember(
            self::CK_SETTINGS_FULL,
            self::TTL_SETTINGS,
            function () {
                $s = Setting::all()->pluck('value', 'key')->toArray();
                $s['logo']       = getLogoUrl();
                $s['store_name'] = getActiveStoreName() ?: ($s['store_name'] ?? null);
                $s['add_stock_while_product_creation'] = $s['add_stock_while_product_creation'] ?? '1';
                $s['warehouse_name']  = Warehouse::whereId($s['default_warehouse'])->first()->name ?? '';
                $s['customer_name']   = Customer::whereId($s['default_customer'])->first()->name ?? '';
                $s['currency_symbol'] = Currency::whereId($s['currency'])->first()->symbol ?? '';
                $s['countries']       = Country::all()->toArray();
                return $s;
            }
        );

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'Setting data retrieved successfully.'
        );
    }

    public function update(Request $request): JsonResponse
    {
        $input = $request->all();
        $settings = $this->settingRepository->updateSettings($input);

        // Flush all settings caches so next read picks up fresh data
        $this->flushSettingsCache();

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'Setting data updated successfully'
        );
    }

    public function clearCache(): JsonResponse
    {
        Artisan::call('cache:clear');

        return $this->sendSuccess(__('messages.success.cache_clear_successfully'));
    }

    public function getFrontSettingsValue(): JsonResponse
    {
        $keyName = [
            'currency',
            'email',
            'company_name',
            'phone',
            'developed',
            'footer',
            'default_language',
            'default_customer',
            'default_warehouse',
            'address',
            'show_app_name_in_sidebar'
        ];
        
        $settings = $this->tenantCache->remember(
            self::CK_SETTINGS_FRONT,
            self::TTL_SETTINGS,
            function () use ($keyName) {
                $s = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();
                $s['logo']            = getLogoUrl();
                $s['warehouse_name']  = Warehouse::whereId($s['default_warehouse'])->first()->name ?? '';
                $s['customer_name']   = Customer::whereId($s['default_customer'])->first()->name ?? '';
                $s['currency_symbol'] = Currency::whereId($s['currency'])->first()->symbol ?? '';
                return $s;
            }
        );

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'value' => $settings]),
            'Setting value retrieved successfully.'
        );
    }

    public function getFrontCms(): JsonResponse
    {
        $store = Store::where('is_default', 1)->first();
        $keyName = [
            'currency',
            'email',
            'company_name',
            'phone',
            'developed',
            'footer',
            'default_language',
            'default_customer',
            'default_warehouse',
            'address',
            'show_app_name_in_sidebar'
        ];
        
        if($store) {
            $settings = Setting::where('tenant_id', $store->tenant_id)->whereIn('key', $keyName)->pluck('value', 'key')->toArray();
        } else {
            $settings = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();
        }
        $settings['logo'] = getLogoUrl();
        $settings['warehouse_name'] = Warehouse::whereId($settings['default_warehouse'])->first()->name ?? '';
        $settings['customer_name'] = Customer::whereId($settings['default_customer'])->first()->name ?? '';
        $settings['currency_symbol'] = Currency::whereId($settings['currency'])->first()->symbol ?? '';

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'value' => $settings]),
            'Setting value retrieved successfully.'
        );
    }

    public function getStates($countryId): JsonResponse
    {
        $states = State::whereCountryId($countryId)->pluck('name');

        return $this->sendResponse(
            new SettingResource(['type' => 'states', 'value' => $states]),
            'States retrieved successfully.'
        );
    }

    public function getMailSettings()
    {
        $envData = $this->settingRepository->getEnvData();

        return $this->sendResponse($envData, 'Mail Credential Retrieved Successfully');
    }

    public function updateMailSettings(Request $request): JsonResponse
    {
        $request->validate([
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_from_address',
            'mail_encryption',
        ]);
        $this->settingRepository->updateMailEnvSetting($request->all());

        Artisan::call('optimize:clear');
        Artisan::call('config:cache');

        return $this->sendSuccess('Mail Settings Save Successfully');
    }

    public function updateReceiptSetting(Request $request)
    {
        $settings = $this->settingRepository->updateReceiptSetting($request->all());
        $this->flushSettingsCache();

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'Setting data updated successfully'
        );
    }

    public function getPosSettings(): JsonResponse
    {
        $settings = $this->tenantCache->remember(
            self::CK_SETTINGS_POS,
            self::TTL_SETTINGS,
            function () {
                $getArray = ['enable_pos_click_audio', 'click_audio', 'show_pos_stock_product'];
                $s = Setting::whereIn('key', $getArray)->pluck('value', 'key')->toArray();
                $s['enable_pos_click_audio'] = $s['enable_pos_click_audio'] ?? false;
                if (!isset($s['click_audio'])) {
                    $s['click_audio'] = asset('images/click_audio.mp3');
                    Setting::updateOrCreate(['key' => 'click_audio'], ['value' => $s['click_audio']]);
                }
                return $s;
            }
        );

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'POS Setting data retrieved successfully.'
        );
    }

    public function updatePosSettings(Request $request): JsonResponse
    {
        $input = $request->all();
        $this->settingRepository->updatePosSettings($input);
        $this->flushSettingsCache();
        return $this->sendSuccess(__('messages.success.pos_settings_updated'));
    }

    public function getDualScreenSettings(): JsonResponse
    {
        $settings = $this->tenantCache->remember(
            self::CK_SETTINGS_DUAL,
            self::TTL_SETTINGS,
            function () {
                $getArray = ['dual_screen_header_text', 'dual_screen_images'];
                $s = Setting::whereIn('key', $getArray)->pluck('value', 'key')->toArray();
                $s['dual_screen_images']      = isset($s['dual_screen_images'])
                    ? json_decode($s['dual_screen_images'], true)
                    : [];
                $s['dual_screen_header_text'] = $s['dual_screen_header_text'] ?? null;
                return $s;
            }
        );

        return $this->sendResponse(
            new SettingResource(['type' => 'dual-screen', 'attributes' => $settings]),
            'POS Setting data retrieved successfully.'
        );
    }

    public function updateDualScreenSettings(Request $request): JsonResponse
    {
        $input = $request->all();
        $this->settingRepository->updateDualScreenSettings($input);
        $this->flushSettingsCache();
        return $this->sendSuccess(__('messages.success.dual_screen_settings_updated'));
    }

    public function sendTestEmail()
    {
        // Get the logged-in user's email
        $userEmail = auth()->user()->email;

        // Send test email to the logged-in user
        $this->settingRepository->sendTestEmail(['email' => $userEmail]);

        return $this->sendSuccess("Test email sent successfully to {$userEmail}");
    }
}
