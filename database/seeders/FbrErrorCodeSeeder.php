<?php

namespace Database\Seeders;

use App\Models\FbrErrorCode;
use Illuminate\Database\Seeder;

/**
 * Seeds a starter set of common FBR Digital Invoice error codes for the Error
 * Center. Occurrence counters are preserved on re-run.
 */
class FbrErrorCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['0001', 'Seller not registered for sales tax', 'The seller NTN/STRN is not registered or active for sales tax.', 'Verify the seller NTN/STRN and FBR registration status.'],
            ['0002', 'Invalid buyer registration number', 'Buyer NTN/CNIC failed validation.', 'Check the buyer NTN/CNIC format and registration.'],
            ['0003', 'Invalid HS Code', 'One or more item HS codes are not recognised by FBR.', 'Use a valid 8-digit HS code for each item.'],
            ['0005', 'Invalid invoice date', 'Invoice date is missing or in the future.', 'Set a valid invoice date (not future-dated).'],
            ['0006', 'Duplicate invoice reference', 'An invoice with this reference already exists.', 'Use a unique invoice reference number.'],
            ['0007', 'Invalid sales tax rate', 'Tax rate is not an allowed value for the item.', 'Apply a valid tax rate per FBR schedule.'],
            ['0008', 'Province mismatch', 'Seller/buyer province is invalid or inconsistent.', 'Select a valid province for seller and buyer.'],
            ['0010', 'Token expired / unauthorized', 'The FBR API token is invalid or expired.', 'Refresh the production/sandbox token for the business.'],
            ['0011', 'Mandatory field missing', 'A required field was not supplied.', 'Complete all required invoice and item fields.'],
            ['0046', 'Invalid UoM for HS Code', 'Unit of measure not allowed for the given HS code.', 'Use the UoM permitted for that HS code.'],
            ['0052', 'Sales tax calculation mismatch', 'Declared sales tax does not match computed value.', 'Recompute item tax = value × rate; resubmit.'],
        ];

        foreach ($codes as [$code, $message, $description, $resolution]) {
            FbrErrorCode::updateOrCreate(
                ['code' => $code],
                ['message' => $message, 'description' => $description, 'resolution' => $resolution]
            );
        }
    }
}
