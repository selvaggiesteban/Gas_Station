<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestSmtp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-smtp {email=marketing1a1oficial@gmail.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify SMTP configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Attempting to send a test email to: {$email}");

        try {
            Mail::raw('This is a test email from AcademicUS to verify SMTP settings.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('AcademicUS - SMTP Test');
            });

            $this->info('Test email sent successfully!');
            Log::info("SMTP Test email sent to {$email}");
        } catch (\Exception $e) {
            $this->error('Failed to send test email.');
            $this->error($e->getMessage());
            Log::error("SMTP Test failed: " . $e->getMessage());
        }
    }
}
