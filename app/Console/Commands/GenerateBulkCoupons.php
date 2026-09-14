<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateBulkCoupons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupons:generate-bulk {amount} {discount_percentage} {expiration_date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a bulk of unique coupons';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amount = (int) $this->argument('amount');
        $discountPercentage = (float) $this->argument('discount_percentage');
        $expirationDate = $this->argument('expiration_date');

        if ($amount <= 0) {
            $this->error("Amount must be greater than 0.");
            return 1;
        }

        if ($discountPercentage <= 0 || $discountPercentage > 100) {
            $this->error("Discount percentage must be between 0 and 100.");
            return 1;
        }

        // Find a user to associate with the coupons (required by DB schema)
        $creatorId = User::role('admin')->first()?->id ?? User::first()?->id;

        if (!$creatorId) {
            $this->error("No user found to assign as creator. Please ensure at least one user exists.");
            return 1;
        }

        $generatedCodes = [];

        for ($i = 0; $i < $amount; $i++) {
            $code = $this->generateUniqueCode();
            
            Coupon::create([
                'code' => $code,
                'type' => Coupon::TYPE_PERCENTAGE,
                'value' => $discountPercentage,
                'max_uses' => 1,
                'used_count' => 0,
                'min_order_amount' => 0,
                'expires_at' => $expirationDate,
                'is_active' => true,
                'applies_to' => Coupon::APPLIES_ALL,
                'created_by' => $creatorId,
            ]);

            $generatedCodes[] = $code;
        }

        $this->info("Successfully generated {$amount} coupons:");
        foreach ($generatedCodes as $code) {
            $this->line($code);
        }

        return 0;
    }

    /**
     * Generate a unique coupon code.
     */
    protected function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
