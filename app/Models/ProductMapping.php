<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMapping extends Model
{
    protected $fillable = [
        'external_product_id', // Stripe/PayPal Product ID
        'provider',            // stripe, paypal, redsys
        'course_id',
        'plan_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
