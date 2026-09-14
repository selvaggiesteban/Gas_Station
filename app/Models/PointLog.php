<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointLog extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'reference_type',
        'reference_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model (Polymorphic)
     */
    public function reference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        $class = "App\\Models\\" . $this->reference_type;
        if (class_exists($class)) {
            return $class::find($this->reference_id);
        }

        return null;
    }
}
