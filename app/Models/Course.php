<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use SoftDeletes;
    protected $fillable = ['title', 'slug', 'description', 'price', 'is_active'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title) . '-' . time();
            }
        });

        static::updating(function ($course) {
            if (empty($course->slug) && $course->isDirty('title')) {
                $course->slug = Str::slug($course->title) . '-' . time();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function blocks()
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }

    /**
     * Get all topics for the course through blocks.
     */
    public function topics()
    {
        return $this->hasManyThrough(Topic::class, Block::class);
    }

    public function tests()
    {
        return $this->hasMany(Test::class);
    }

    /**
     * Products for this course (subscription options)
     */
    public function products()
    {
        return $this->hasMany(Product::class)->orderBy('display_order')->orderBy('price');
    }

    /**
     * Active products only
     */
    public function activeProducts()
    {
        return $this->products()->where('is_active', true);
    }
}
