<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'price', 'duration_in_days'];

    public function getTranslatedNameAttribute()
    {
        return match($this->slug) {
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual',
            'annual' => 'Anual',
            'lifetime' => 'Vitalicio',
            default => match($this->name) {
                'Monthly Access' => 'Acceso Mensual',
                'Quarterly Access' => 'Acceso Trimestral',
                'Yearly Pro' => 'Acceso Anual Pro',
                'Monthly' => 'Mensual',
                'Quarterly' => 'Trimestral',
                'Yearly' => 'Anual',
                'Annual' => 'Anual',
                default => $this->name,
            }
        };
    }
}
