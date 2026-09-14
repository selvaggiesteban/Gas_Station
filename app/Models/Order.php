<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'coupon_id', 'email', 'name', 'nif', 'phone', 'address', 'city', 'province', 'postal_code', 
        'company_name', 'payment_method', 'stripe_session_id'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public static function generateOrderNumber()
    {
        return 'ORD-' . strtoupper(uniqid());
    }
}
