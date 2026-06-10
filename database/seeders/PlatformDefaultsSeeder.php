<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds platform-level defaults so the billing/add-on/FBR use cases work out of
 * the box: global discount, add-on rates, FBR price (global + PK), and a couple
 * of payout bank accounts (PK + US). Idempotent.
 */
class PlatformDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        // Global billing settings.
        $settings = [
            'long_term_discount_percent'    => 10,
            'long_term_discount_min_months' => 12,
            'addon_shop_rate'               => 5,
            'addon_user_rate'               => 2,
            'addon_product_rate'            => 0.01,
            'fbr_monthly_price'             => 1500,   // global default (PKR-ish)
            'fbr_yearly_price'              => 15000,
            // Pakistan-specific FBR override.
            'fbr_monthly_price_PK'          => 1200,
            'fbr_yearly_price_PK'           => 12000,
            // Subscription payment methods. Manual proof upload ("request") is on
            // by default; online checkout is opt-in per platform.
            'billing.method.request'        => '1',
            'billing.method.checkout'       => '1',
            // Global checkout channel: a single hosted payment link.
            'billing.checkout.channels'     => json_encode([
                ['key' => 'link', 'label' => 'Online Payment Link', 'type' => 'link', 'account' => '', 'url' => '', 'instructions' => 'Open the secure link to complete your payment.'],
            ]),
            // Pakistan checkout channels: mobile wallets + local banks.
            'billing.checkout.channels_PK'  => json_encode([
                ['key' => 'jazzcash', 'label' => 'JazzCash',  'type' => 'wallet', 'account' => '0300-1234567', 'url' => '', 'instructions' => 'Send to our JazzCash number, then enter the TID.'],
                ['key' => 'easypaisa','label' => 'Easypaisa',  'type' => 'wallet', 'account' => '0345-7654321', 'url' => '', 'instructions' => 'Send to our Easypaisa number, then enter the TID.'],
                ['key' => 'hbl',      'label' => 'HBL Bank',    'type' => 'bank',   'account' => 'PK00HABB0000000000000000', 'url' => '', 'instructions' => 'Bank transfer to our HBL account, then enter the reference.'],
                ['key' => 'meezan',   'label' => 'Meezan Bank', 'type' => 'bank',   'account' => 'PK00MEZN0000000000000000', 'url' => '', 'instructions' => 'Bank transfer to our Meezan account, then enter the reference.'],
                ['key' => 'ubl',      'label' => 'UBL Bank',    'type' => 'bank',   'account' => 'PK00UNIL0000000000000000', 'url' => '', 'instructions' => 'Bank transfer to our UBL account, then enter the reference.'],
                ['key' => 'other',    'label' => 'Other Bank/Wallet', 'type' => 'bank', 'account' => '', 'url' => '', 'instructions' => 'Transfer and enter the reference / TID.'],
            ]),
        ];
        foreach ($settings as $key => $value) {
            PlatformSetting::set($key, $value);
        }

        // Payout bank accounts (per country).
        BankAccount::updateOrCreate(
            ['country' => 'PK', 'bank_name' => 'Meezan Bank'],
            [
                'account_title'  => 'NoovaPOS (Pvt) Ltd',
                'account_number' => '0123-4567890123',
                'iban'           => 'PK00MEZN0000000000000000',
                'currency'       => 'PKR',
                'instructions'   => 'Use your tenant subdomain as the payment reference.',
                'status'         => true,
            ]
        );

        BankAccount::updateOrCreate(
            ['country' => 'US', 'bank_name' => 'Chase'],
            [
                'account_title'  => 'NoovaPOS Inc',
                'account_number' => '000123456789',
                'swift'          => 'CHASUS33',
                'currency'       => 'USD',
                'instructions'   => 'Reference your tenant subdomain in the wire memo.',
                'status'         => true,
            ]
        );
    }
}
