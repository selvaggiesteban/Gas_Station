<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cuerpo extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($c) {
            if (empty($c->slug)) {
                $c->slug = Str::slug($c->name);
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'cuerpo_user')->withTimestamps();
    }
}
