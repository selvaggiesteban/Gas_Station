<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Course;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Order;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MassiveHistoricalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Generando historial con campos financieros exactos...');

        for ($i = 1; $i <= 3; $i++) {
            $student = User::updateOrCreate(
                ['email' => "historial{$i}@academicus.sim"],
                [
                    'name' => "Estudiante Historial $i",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'gender' => 'neutral'
                ]
            );

            $course = Course::first();
            if (!$course) continue;
            
            $product = Product::where('course_id', $course->id)->first();
            if (!$product) continue;

            // SUSCRIPCIÓN CON FECHAS OBLIGATORIAS
            $sub = Subscription::updateOrCreate(
                ['user_id' => $student->id, 'product_id' => $product->id],
                [
                    'status' => 'active',
                    'starts_at' => Carbon::now()->subMonths(1),
                    'ends_at' => Carbon::now()->addMonths(1),
                    'stripe_subscription_id' => "sub_hist_$i"
                ]
            );

            // ORDER: CAMPOS OBLIGATORIOS FALTANTES
            $order = Order::updateOrCreate(
                ['order_number' => "ORD-HIST-$i"],
                [
                    'user_id' => $student->id,
                    'status' => 'paid',
                    'subtotal' => 49.50,
                    'tax_amount' => 10.40,
                    'total_amount' => 59.90,
                    'email' => $student->email,
                    'name' => $student->name,
                    'nif' => '12345678Z',
                    'phone' => '600000000',
                    'address' => 'Calle Simulación 1',
                    'city' => 'Madrid',
                    'province' => 'Madrid',
                    'postal_code' => '28001',
                    'payment_method' => 'stripe'
                ]
            );

            // INVOICE
            Invoice::updateOrCreate(
                ['invoice_number' => "INV-HIST-$i"],
                [
                    'user_id' => $student->id,
                    'subscription_id' => $sub->id,
                    'issue_date' => now(),
                    'subtotal' => 49.50,
                    'tax_amount' => 10.40,
                    'total_amount' => 59.90,
                    'tax_rate' => 21.00,
                    'currency' => 'EUR',
                    'status' => 'paid'
                ]
            );
        }
    }
}
