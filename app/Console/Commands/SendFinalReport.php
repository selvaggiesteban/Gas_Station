<?php

namespace App\Console\Commands;

use App\Mail\DeploymentCompletionReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFinalReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-final-report {email=selvaggi.esteban@gmail.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the final deployment completion report email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Sending final deployment report to {$email}...");

        try {
            Mail::to($email)->send(new DeploymentCompletionReport());
            $this->info('Report sent successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to send report: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
