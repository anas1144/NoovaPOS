<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    /**
     * Seeds the default data needed for ANY context (central or tenant).
     * Safe to re-run — all creates are guarded by existence checks.
     */
    public function run(): void
    {
        // Walk-in customer
        if (! Customer::where('email', 'walkin@noovapos.com')->exists()) {
            Customer::create([
                'name'    => 'Walk-in Customer',
                'email'   => 'walkin@noovapos.com',
                'phone'   => '0000000000',
                'country' => 'PK',
                'city'    => 'Karachi',
                'address' => 'Walk-in',
            ]);
        }

        // Default warehouse
        if (! Warehouse::where('email', 'warehouse@noovapos.com')->exists()) {
            Warehouse::create([
                'name'     => 'Main Warehouse',
                'phone'    => '0000000000',
                'country'  => 'PK',
                'city'     => 'Karachi',
                'email'    => 'warehouse@noovapos.com',
                'zip_code' => '75000',
            ]);
        }

        // USD currency  — symbol MUST be $ not ₹
        if (! Currency::where('code', 'USD')->exists()) {
            Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);
        }

        // PKR currency
        if (! Currency::where('code', 'PKR')->exists()) {
            Currency::create(['name' => 'Pakistani Rupee', 'code' => 'PKR', 'symbol' => '₨']);
        }

        $logo = 'images/noovapos.png';

        $defaults = [
            'currency'          => '1',
            'email'             => 'support@noovapos.com',
            'company_name'      => 'NoovaPOS',
            'phone'             => '+92 300 0000000',
            'developed'         => '',
            'footer'            => '',
            'default_language'  => '1',
            'default_customer'  => '1',
            'default_warehouse' => '1',
            'address'           => 'Karachi, Pakistan',
            'stripe_key'        => '',
            'stripe_secret'     => '',
            'sms_gateway'       => '1',
            'twillo_sid'        => '',
            'twillo_token'      => '',
            'twillo_from'       => '',
            'smtp_host'         => 'smtp.mailtrap.io',
            'smtp_port'         => '2525',
            'smtp_username'     => '',
            'smtp_password'     => '',
            'smtp_Encryption'   => 'tls',
            'logo'              => $logo,
        ];

        foreach ($defaults as $key => $value) {
            if (! keyExist($key)) {
                Setting::create(['key' => $key, 'value' => $value]);
            }
        }
    }
}
