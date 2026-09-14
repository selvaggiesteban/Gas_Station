<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Support\Str;

class GenerateMissingSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-missing-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate slugs for courses and topics that do not have one.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking courses for missing slugs...');
        $courses = Course::whereNull('slug')->orWhere('slug', '')->get();
        foreach ($courses as $course) {
            $course->slug = Str::slug($course->title);
            $course->save();
            $this->line("Generated slug for course: {$course->title} -> {$course->slug}");
        }

        $this->info('Checking topics for missing slugs...');
        $topics = Topic::whereNull('slug')->orWhere('slug', '')->get();
        foreach ($topics as $topic) {
            $topic->slug = Str::slug($topic->title);
            $topic->save();
            $this->line("Generated slug for topic: {$topic->title} -> {$topic->slug}");
        }

        $this->info('Slug generation complete.');
    }
}
