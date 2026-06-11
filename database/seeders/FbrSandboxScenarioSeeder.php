<?php

namespace Database\Seeders;

use App\Models\FbrSandboxScenario;
use Illuminate\Database\Seeder;

/**
 * Seeds the FBR DI sandbox scenarios SN001…SN028. Titles follow the FBR DI
 * sandbox scenario list; payloads start empty and can be filled per business in
 * the Sandbox Testing Center. Re-running preserves run results.
 */
class FbrSandboxScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            'SN001' => 'Goods at standard rate to registered buyers',
            'SN002' => 'Goods at standard rate to unregistered buyers',
            'SN003' => 'Sale of steel (melting and re-rolling)',
            'SN004' => 'Sale by Ship Breakers',
            'SN005' => 'Reduced rate sale',
            'SN006' => 'Exempt goods sale',
            'SN007' => 'Zero-rated sale',
            'SN008' => 'Sale of 3rd schedule goods',
            'SN009' => 'Cotton ginners',
            'SN010' => 'Telecom services',
            'SN011' => 'Toll manufacturing',
            'SN012' => 'Petroleum products',
            'SN013' => 'Electricity supply to retailers',
            'SN014' => 'Gas to CNG stations',
            'SN015' => 'Mobile phones',
            'SN016' => 'Processing / conversion of goods',
            'SN017' => 'Goods (FED in ST mode)',
            'SN018' => 'Services (FED in ST mode)',
            'SN019' => 'Services',
            'SN020' => 'Electric vehicle',
            'SN021' => 'Cement / concrete block',
            'SN022' => 'Potassium chlorate',
            'SN023' => 'CNG sales',
            'SN024' => 'Goods as per SRO.297(I)/2023',
            'SN025' => 'Drugs sold at fixed ST rate (8th schedule)',
            'SN026' => 'Sale to end consumer by retailers',
            'SN027' => '3rd schedule goods sold to end consumer by retailers',
            'SN028' => 'Goods at standard rate to end consumers',
        ];

        foreach ($titles as $code => $title) {
            FbrSandboxScenario::updateOrCreate(
                ['code' => $code],
                ['title' => $title, 'description' => $title]
            );
        }
    }
}
