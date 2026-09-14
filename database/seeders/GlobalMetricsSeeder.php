<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class GlobalMetricsSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $user = User::updateOrCreate(
                ['email' => "metricas{$i}@example.com"],
                ['name' => "Alumno $i", 'password' => Hash::make('password'), 'email_verified_at' => now(), 'gender' => 'neutral']
            );
            if (!$user->hasRole('Student')) $user->assignRole('Student');

            $product = Product::first();
            if ($product) {
                $sub = Subscription::updateOrCreate(
                    ['user_id' => $user->id, 'product_id' => $product->id],
                    [
                        'status' => 'active', 
                        'stripe_subscription_id' => "sub_metric_$i",
                        'starts_at' => Carbon::now()->subDays(10),
                        'ends_at' => Carbon::now()->addDays(20),
                    ]
                );

                $order = Order::updateOrCreate(
                    ['order_number' => "ORD-METRIC-$i"],
                    [
                        'user_id' => $user->id,
                        'status' => 'paid',
                        'subtotal' => 10.00,
                        'tax_amount' => 2.10,
                        'total_amount' => 12.10,
                        'email' => $user->email,
                        'name' => $user->name,
                        'nif' => "1234567{$i}Z",
                        'address' => 'Sede Metrics',
                        'city' => 'Madrid',
                        'province' => 'Madrid',
                        'postal_code' => '28000',
                        'payment_method' => 'stripe'
                    ]
                );

                Invoice::updateOrCreate(
                    ['invoice_number' => "INV-METRIC-$i"],
                    [
                        'user_id' => $user->id,
                        'subscription_id' => $sub->id,
                        'issue_date' => now(),
                        'subtotal' => 10.00,
                        'tax_amount' => 2.10,
                        'total_amount' => 12.10,
                        'tax_rate' => 21.00,
                        'currency' => 'EUR',
                        'status' => 'paid',
                    ]
                );
            }
        }
    }
}
