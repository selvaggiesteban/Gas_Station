<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopicResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'title',
        'file_path',
        'resource_type',
        'file_size',
        'download_count',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Resource type constants
     */
    const TYPE_SUMMARY = 'summary';
    const TYPE_DIAGRAM = 'diagram';
    const TYPE_MIND_MAP = 'mind_map';
    const TYPE_EXTRA = 'extra';

    /**
     * Relationship to topic
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get file size in human readable format
     */
    public function getHumanFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->resource_type) {
            self::TYPE_SUMMARY => 'Resumen',
            self::TYPE_DIAGRAM => 'Diagrama',
            self::TYPE_MIND_MAP => 'Mapa Mental',
            self::TYPE_EXTRA => 'Extra',
            default => $this->resource_type,
        };
    }

    /**
     * Scope for active resources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('resource_type', $type);
    }

    /**
     * Scope ordered by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at', 'desc');
    }
}
