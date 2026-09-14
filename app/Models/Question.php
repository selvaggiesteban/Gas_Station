<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['test_id', 'text', 'explanation', 'type', 'max_score', 'fill_blank_text', 'open_ended_max_words', 'display_order'];

    protected $casts = [
        'max_score' => 'integer',
        'open_ended_max_words' => 'integer',
        'display_order' => 'integer',
    ];

    protected $attributes = [
        'type' => 'multiple_choice',
        'max_score' => 10,
        'open_ended_max_words' => 500,
        'display_order' => 0,
    ];

    /**
     * Type constants
     */
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_PRACTICAL_CASE = 'practical_case';
    const TYPE_FIVE_OPTIONS = 'five_options';
    const TYPE_FILL_BLANK = 'fill_blank';
    const TYPE_OPEN_ENDED = 'open_ended';

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function practicalCaseSubmissions()
    {
        return $this->hasMany(PracticalCaseSubmission::class);
    }

    /**
     * Check if this is a multiple choice question
     */
    public function isMultipleChoice(): bool
    {
        return in_array($this->type, [self::TYPE_MULTIPLE_CHOICE, self::TYPE_FIVE_OPTIONS]);
    }

    /**
     * Check if this is a practical case question
     */
    public function isPracticalCase(): bool
    {
        return $this->type === self::TYPE_PRACTICAL_CASE;
    }

    /**
     * Check if this is a five options question
     */
    public function isFiveOptions(): bool
    {
        return $this->type === self::TYPE_FIVE_OPTIONS;
    }

    /**
     * Check if this is a fill in the blank question
     */
    public function isFillBlank(): bool
    {
        return $this->type === self::TYPE_FILL_BLANK;
    }

    /**
     * Check if this is an open ended question
     */
    public function isOpenEnded(): bool
    {
        return $this->type === self::TYPE_OPEN_ENDED;
    }

    /**
     * Check if this question requires manual review
     */
    public function requiresManualReview(): bool
    {
        return in_array($this->type, [self::TYPE_PRACTICAL_CASE, self::TYPE_OPEN_ENDED]);
    }

    /**
     * Check if this question has automated grading
     */
    public function hasAutomatedGrading(): bool
    {
        return in_array($this->type, [self::TYPE_MULTIPLE_CHOICE, self::TYPE_FIVE_OPTIONS, self::TYPE_FILL_BLANK]);
    }

    /**
     * Get expected number of options for this question type
     */
    public function getExpectedOptionsCount(): ?int
    {
        return match($this->type) {
            self::TYPE_MULTIPLE_CHOICE => 4,
            self::TYPE_FIVE_OPTIONS => 5,
            default => null,
        };
    }
}
