<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Order;
use App\Models\Invoice;
use Carbon\Carbon;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::role('Student')->first();
        if (!$student) return;

        $product = Product::updateOrCreate(
            ['type' => 'monthly'],
            [
                'name' => 'Suscripción Pro',
                'course_id' => \App\Models\Course::first()?->id ?? 1,
                'price' => 29.99,
                'duration_days' => 30,
                'is_active' => true
            ]
        );

        $subscription = Subscription::updateOrCreate(
            ['user_id' => $student->id, 'product_id' => $product->id],
            [
                'status' => 'active',
                'stripe_subscription_id' => 'sub_final_shield',
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addDays(30),
            ]
        );

        $order = Order::updateOrCreate(
            ['order_number' => "ORD-SEED-001"],
            [
                'user_id' => $student->id,
                'status' => 'paid',
                'subtotal' => 24.78,
                'tax_amount' => 5.21,
                'total_amount' => 29.99,
                'email' => $student->email,
                'name' => $student->name,
                'nif' => '11111111H',
                'address' => 'Sede',
                'city' => 'Madrid',
                'province' => 'Madrid',
                'postal_code' => '28000',
                'payment_method' => 'stripe'
            ]
        );

        Invoice::updateOrCreate(
            ['invoice_number' => 'FACT-2026-FINAL'],
            [
                'user_id' => $student->id,
                'subscription_id' => $subscription->id,
                'issue_date' => now(),
                'subtotal' => 24.78,
                'tax_amount' => 5.21,
                'total_amount' => 29.99,
                'tax_rate' => 21.00,
                'currency' => 'EUR',
                'status' => 'paid',
            ]
        );
    }
}
