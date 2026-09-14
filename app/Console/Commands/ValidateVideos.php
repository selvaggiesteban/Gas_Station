<?php

namespace App\Console\Commands;

use App\Models\Topic;
use Illuminate\Console\Command;

class ValidateVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:validate-videos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Iterate through all Topics and flag any video_url that seems to be a standard public YouTube link instead of unlisted/private.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $topics = Topic::whereNotNull('video_url')->get();
        $total = $topics->count();
        $flagged = 0;

        $this->info("Checking $total topics for video privacy...");

        foreach ($topics as $topic) {
            $url = $topic->video_url;
            
            // Basic detection for standard YouTube watch links which are often public
            // Unlisted videos use the same URL format, but this command flags them for manual verification
            if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/)/i', $url)) {
                $this->warn("Topic ID: {$topic->id} | Title: {$topic->title}");
                $this->line("URL: $url");
                $this->line("Reason: Standard YouTube link format detected. Verify if it is set to 'Unlisted' or 'Private'.");
                $this->line("------------------------------------------------------------");
                $flagged++;
            }
        }

        if ($flagged === 0) {
            $this->info("No suspicious public links found.");
        } else {
            $this->error("Found $flagged topics with standard YouTube links that need manual privacy verification.");
        }

        return Command::SUCCESS;
    }
}
