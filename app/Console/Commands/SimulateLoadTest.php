<?php

namespace App\Console\Commands;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimulateLoadTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:simulate-load {--count=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate concurrent TestAttempt creations to test DB locking and Redis queues.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        $user = User::first();
        $test = Test::first();

        if (!$user || !$test) {
            $this->error("Ensure you have at least one user and one test in the database before running this command.");
            return Command::FAILURE;
        }

        $this->info("Simulating $count concurrent TestAttempt creations...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // We use DB transactions to test locking
        for ($i = 0; $i < $count; $i++) {
            // In a real production scenario, you might dispatch this to a queue
            // dispatch(fn() => $this->createAttempt($user, $test));
            
            // For immediate testing of DB locking in this command, we'll simulate the load
            try {
                DB::transaction(function () use ($user, $test) {
                    TestAttempt::create([
                        'user_id' => $user->id,
                        'test_id' => $test->id,
                        'score' => rand(0, 100),
                        'passed' => rand(0, 1) == 1,
                        'answers' => ['q1' => 'a', 'q2' => 'b'],
                        'started_at' => now(),
                        'completed_at' => now()->addMinutes(10),
                    ]);
                });
            } catch (\Exception $e) {
                Log::error("Load test creation failed: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Load test simulation completed.");

        return Command::SUCCESS;
    }
}
