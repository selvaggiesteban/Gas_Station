<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopicPdfVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'version_number',
        'title',
        'file_path',
        'upload_date',
        'is_current',
        'change_notes',
        'uploaded_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'upload_date' => 'datetime',
        'is_current' => 'boolean',
    ];

    /**
     * Relationship to topic
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Relationship to uploader
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Set as current version (and unset others)
     */
    public function setAsCurrent(): void
    {
        // Unset other current versions for this topic
        self::where('topic_id', $this->topic_id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Set this as current
        $this->update(['is_current' => true]);
    }

    /**
     * Get next version number for a topic
     */
    public static function getNextVersionNumber(int $topicId): int
    {
        $latest = self::where('topic_id', $topicId)
            ->orderBy('version_number', 'desc')
            ->first();

        return $latest ? $latest->version_number + 1 : 1;
    }

    /**
     * Scope for current versions
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope for specific topic
     */
    public function scopeForTopic($query, int $topicId)
    {
        return $query->where('topic_id', $topicId);
    }

    /**
     * Scope ordered by version
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('version_number', 'desc');
    }
}
