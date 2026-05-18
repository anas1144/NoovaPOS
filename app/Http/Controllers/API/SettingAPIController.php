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

    public function __construct(SettingRepository $productRepository)
    {
        $this->settingRepository = $productRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $settings['logo'] = getLogoUrl();
        $settings['store_name'] = getActiveStoreName() ? getActiveStoreName() : ($settings['store_name'] ?? null);
        $settings['add_stock_while_product_creation'] = isset($settings['add_stock_while_product_creation']) ? $settings['add_stock_while_product_creation'] : '1';
        $settings['warehouse_name'] = Warehouse::whereId($settings['default_warehouse'])->first()->name ?? '';
        $settings['customer_name'] = Customer::whereId($settings['default_customer'])->first()->name ?? '';
        $settings['currency_symbol'] = Currency::whereId($settings['currency'])->first()->symbol ?? '';
        $settings['countries'] = Country::all();

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'Setting data retrieved successfully.'
        );
    }

    public function update(Request $request): JsonResponse
    {
        $input = $request->all();
        $settings = $this->settingRepository->updateSettings($input);

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
        
        $settings = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();
        $settings['logo'] = getLogoUrl();
        $settings['warehouse_name'] = Warehouse::whereId($settings['default_warehouse'])->first()->name ?? '';
        $settings['customer_name'] = Customer::whereId($settings['default_customer'])->first()->name ?? '';
        $settings['currency_symbol'] = Currency::whereId($settings['currency'])->first()->symbol ?? '';

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

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'Setting data updated successfully'
        );
    }

    public function getPosSettings(): JsonResponse
    {
        $getArray = [
            'enable_pos_click_audio',
            'click_audio',
            'show_pos_stock_product',
        ];

        $settings = Setting::whereIn('key', $getArray)->pluck('value', 'key')->toArray();
        $settings['enable_pos_click_audio'] = $settings['enable_pos_click_audio'] ?? false;
        if (!isset($settings['click_audio'])) {
            $settings['click_audio'] = asset('images/click_audio.mp3');
            Setting::updateOrCreate(['key' => 'click_audio'], ['value' => $settings['click_audio']]);
        }

        return $this->sendResponse(
            new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'POS Setting data retrieved successfully.'
        );
    }

    public function updatePosSettings(Request $request): JsonResponse
    {
        $input = $request->all();
        $this->settingRepository->updatePosSettings($input);
        return $this->sendSuccess(__('messages.success.pos_settings_updated'));
    }

    public function getDualScreenSettings(): JsonResponse
    {
        $getArray = [
            'dual_screen_header_text',
            'dual_screen_images',
        ];

        $settings = Setting::whereIn('key', $getArray)->pluck('value', 'key')->toArray();
        if (isset($settings['dual_screen_images'])) {
            $settings['dual_screen_images'] = json_decode($settings['dual_screen_images'], true);
        } else {
            $settings['dual_screen_images'] = [];
        }
        $settings['dual_screen_header_text'] = $settings['dual_screen_header_text'] ?? null;
        
        return $this->sendResponse(
            new SettingResource(['type' => 'dual-screen', 'attributes' => $settings]),
            'POS Setting data retrieved successfully.'
        );
    }

    public function updateDualScreenSettings(Request $request): JsonResponse
    {
        $input = $request->all();
        $this->settingRepository->updateDualScreenSettings($input);
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
