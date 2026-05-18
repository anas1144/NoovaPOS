<?php

namespace App\Repositories;

use App\Models\BaseUnit;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\MailTemplate;
use App\Models\MultiTenant;
use App\Models\Setting;
use App\Models\SmsSetting;
use App\Models\SmsTemplate;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\UserStore;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class StoreRepository
 */
class StoreRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
    ];

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Store::class;
    }

    public function store($input)
    {
        try {
            DB::beginTransaction();

            $store = Store::create([
                'name' => $input['name'],
                'user_id' => Auth::id(),
            ]);

            $tenant = MultiTenant::create(['store_id' => $store->id]);

            $store->update(['tenant_id' => $tenant->id]);
            $this->createDomain($tenant->id, $input['domain'] ?? $input['subdomain'] ?? null);

            $this->storeDefaultSettings($tenant->id);

            DB::commit();
            return $store;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }

    public function updateStore($input, $storeId)
    {
        try {
            DB::beginTransaction();

            $store = Store::find($storeId);
            $store->update([
                'name' => $input['name'],
            ]);

            DB::commit();
            return $store;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }

    public function deleteStore($storeId)
    {
        try {
            DB::beginTransaction();

            $store = Store::find($storeId);
            $tenant = MultiTenant::where('id', $store->tenant_id)
                ->where('store_id', $store->id)
                ->first();

            if ($tenant) {
                $user = Auth::user();
                if ($user->tenant_id == $tenant->id) {
                    throw new UnprocessableEntityHttpException(__('messages.error.cannot_delete_active_store'));
                }

                if (UserStore::where('store_id', $store->id)->exists()) {
                    throw new UnprocessableEntityHttpException(__('messages.error.cannot_delete_assigned_store'));
                }

                $tenant->deleteDatabaseIfExists();

                $tenant->unsetEventDispatcher();
                $tenant->forceDelete();
            }

            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }

    public function createDomain(string $tenantId, ?string $domain): ?Domain
    {
        if (empty($domain)) {
            return null;
        }

        $domain = $this->normalizeDomain($domain);

        if (in_array($domain, config('tenancy.central_domains', []), true)) {
            throw new UnprocessableEntityHttpException('Domain is reserved for the central app.');
        }

        $existingDomain = Domain::where('domain', $domain)->first();
        if ($existingDomain && $existingDomain->tenant_id !== $tenantId) {
            throw new UnprocessableEntityHttpException('Domain is already assigned to another tenant.');
        }

        return $existingDomain ?: Domain::create([
            'domain' => $domain,
            'tenant_id' => $tenantId,
        ]);
    }

    public function normalizeDomain(string $domain): string
    {
        $domain = Str::lower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = trim(explode('/', $domain)[0]);

        if (!str_contains($domain, '.')) {
            $baseDomain = Str::lower(trim((string) env('TENANT_BASE_DOMAIN', 'pos.com'), '.'));
            $domain = $domain . '.' . $baseDomain;
        }

        return $domain;
    }

    public function storeDefaultSettings($tenantId, ?string $sourceTenantId = null)
    {
        try {
            DB::beginTransaction();

            // Base Units
            $baseUnits = ['piece', 'meter', 'kilogram'];
            foreach ($baseUnits as $baseUnit) {
                BaseUnit::create([
                    'tenant_id' => $tenantId,
                    'name' => $baseUnit,
                    'is_default' => 1,
                ]);
            }

            // Mail Templates
            $mailTemplates = [
                [
                    'tenant_id' => $tenantId,
                    'template_name' => 'GREETING TO CUSTOMER ON SALES !',
                    'content' => '<p>Hi, {customer_name}</p><p>Your sales Id is {sales_id}</p><p>Sales Date: {sales_date}</p><p>Total Amount: {sales_amount}</p><p>You have paid: {paid_amount}</p><p>Due amount: {due_amount}</p><p>Regards,  {app_name}</p>',
                    'type' => MailTemplate::MAIL_TYPE_SALE
                ],
                [
                    'tenant_id' => $tenantId,
                    'template_name' => 'GREETING TO CUSTOMER ON SALES RETURN !',
                    'content' => '<p>Hi, {customer_name}</p><p>Your sales return Id is {sales_return_id}</p><p>Sales return Date: {sales_return_date}</p><p>Total Amount: {sales_return_amount}</p><p>Regards,  {app_name}</p>',
                    'type' => MailTemplate::MAIL_TYPE_SALE_RETURN,
                ]
            ];
            foreach ($mailTemplates as $mailTemplate) {
                MailTemplate::create($mailTemplate);
            }

            // SMS Settings
            $smsSettings = [
                'url' => 'http://test.com/api/test.php',
                'mobile_key' => '',
                'message_key' => '',
                'payload' => '',
            ];
            foreach ($smsSettings as $key => $value) {
                SmsSetting::create([
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'value' => $value
                ]);
            }

            // SMS Templates
            $smsTemplates = [
                [
                    'tenant_id' => $tenantId,
                    'template_name' => 'GREETING TO CUSTOMER ON SALES !',
                    'content' => 'Hi {customer_name}, Your sales Id is {sales_id}, Sales Date {sales_date}, Total Amount {sales_amount}, You have paid {paid_amount}, and customer total due amount is {due_amount} Thank you visit again',
                    'type' => SmsTemplate::SMS_TYPE_SALE,
                ],
                [
                    'tenant_id' => $tenantId,
                    'template_name' => 'GREETING TO CUSTOMER ON SALES RETURN !',
                    'content' => 'Hi {customer_name}, Your sales return Id is {sales_return_id}, Sales return Date {sales_return_date}, and Total Amount is {sales_return_amount} Thank you visit again',
                    'type' => SmsTemplate::SMS_TYPE_SALE_RETURN,
                ]
            ];
            foreach ($smsTemplates as $smsTemplate) {
                SmsTemplate::create($smsTemplate);
            }

            // Customer
            $customer = Customer::create([
                'tenant_id' => $tenantId,
                'name' => 'walk-in-customer',
                'email' => 'customer+' . $tenantId . '@nexus-gls.com',
                'phone' => '123456789',
                'country' => 'india',
                'city' => 'mumbai',
                'address' => 'Dr Deshmukh Marg , mumbai',
            ]);
            // Warehouse
            $warehouse = Warehouse::create([
                'tenant_id' => $tenantId,
                'name' => 'warehouse',
                'phone' => '123456789',
                'country' => 'india',
                'city' => 'mumbai',
                'email' => 'warehouse+' . $tenantId . '@nexus-gls.com',
                'zip_code' => '12345',
            ]);

            $setting = [
                'currency',
                'email',
                'company_name',
                'phone',
                'developed',
                'footer',
                'default_language',
                'address',
                'show_version_on_footer',
                'country',
                'state',
                'city',
                'postcode',
                'date_format',
                'purchase_code',
                'purchase_return_code',
                'sale_code',
                'sale_return_code',
                'expense_code',
                'is_currency_right',
                'show_app_name_in_sidebar',
                'add_stock_while_product_creation'
            ];

            $sourceTenantId = $sourceTenantId ?? (Auth::check() ? Auth::user()->tenant_id : null);
            $defaultSettingsQuery = Setting::withoutGlobalScope('tenant')->whereIn('key', $setting);

            if ($sourceTenantId) {
                $defaultSettingsQuery->where('tenant_id', $sourceTenantId);
            }

            $defaultSettings = $defaultSettingsQuery->pluck('value', 'key')->toArray();

            $settings = [
                'currency' => $defaultSettings['currency'] ?? Currency::first()->id,
                'email' => $defaultSettings['email'] ?? 'info@nexus-gls.com',
                // 'company_name' => $defaultSettings['company_name'] ?? 'NexusPos',
                'phone' => $defaultSettings['phone'] ?? '123456789',
                'developed' => $defaultSettings['developed'] ?? 'nexus-gls.com',
                'footer' => $defaultSettings['footer'] ?? 'nexus-gls.com',
                'default_language' => $defaultSettings['default_language'] ?? 'en',
                'default_customer' => $customer->id,
                'default_warehouse' => $warehouse->id,
                'address' => $defaultSettings['address'] ?? 'NexusPos',
                'show_version_on_footer' => $defaultSettings['show_version_on_footer'] ?? '1',
                'country' => $defaultSettings['country'] ?? 'India',
                'state' => $defaultSettings['state'] ?? 'Gujarat',
                'city' => $defaultSettings['city'] ?? 'Surat',
                'postcode' => $defaultSettings['postcode'] ?? '12345',
                'date_format' => $defaultSettings['date_format'] ?? 'y-m-d',
                'purchase_code' => $defaultSettings['purchase_code'] ?? 'PU',
                'purchase_return_code' => $defaultSettings['purchase_return_code'] ?? 'PR',
                'sale_code' => $defaultSettings['sale_code'] ?? 'SA',
                'sale_return_code' => $defaultSettings['sale_return_code'] ?? 'SR',
                'expense_code' => $defaultSettings['expense_code'] ?? 'EX',
                'is_currency_right' => $defaultSettings['is_currency_right'] ?? '0',
                'add_stock_while_product_creation' => $defaultSettings['add_stock_while_product_creation'] ?? '1',
                'show_app_name_in_sidebar' => $defaultSettings['show_app_name_in_sidebar'] ?? '1',
            ];

            foreach ($settings as $key => $value) {
                Setting::create([
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'value' => $value
                ]);
            }

            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }
}
