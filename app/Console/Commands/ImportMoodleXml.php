<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

class ImportMoodleXml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:moodle-xml {file_path} {test_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import multiple-choice questions from a Moodle XML file to a specific test';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file_path');
        $testId = $this->argument('test_id');

        if (!file_exists($filePath)) {
            $this->error("File not found at path: {$filePath}");
            return 1;
        }

        try {
            $xmlString = file_get_contents($filePath);
            $xml = new SimpleXMLElement($xmlString);
        } catch (\Exception $e) {
            $this->error("Failed to parse XML: " . $e->getMessage());
            return 1;
        }

        $importedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($xml->question as $q) {
                $type = (string) $q['type'];
                
                // Only process multiple choice or true/false
                if (!in_array($type, ['multichoice', 'truefalse'])) {
                    continue;
                }

                $questionText = (string) $q->questiontext->text;
                $questionText = trim(strip_tags($questionText));

                if (empty($questionText)) {
                    continue; // Skip empty questions
                }

                $explanation = '';
                if (isset($q->generalfeedback->text)) {
                    $explanation = trim(strip_tags((string) $q->generalfeedback->text));
                }

                $question = Question::create([
                    'test_id' => $testId,
                    'text' => $questionText,
                    'explanation' => $explanation,
                    'type' => Question::TYPE_MULTIPLE_CHOICE,
                    'max_score' => 10,
                ]);

                if (isset($q->answer)) {
                    foreach ($q->answer as $ans) {
                        $fraction = (float) $ans['fraction'];
                        $isCorrect = $fraction > 0;
                        $optionText = trim(strip_tags((string) $ans->text));

                        Option::create([
                            'question_id' => $question->id,
                            'text' => $optionText ?: 'Empty Option',
                            'is_correct' => $isCorrect,
                        ]);
                    }
                }

                $importedCount++;
            }

            DB::commit();
            $this->info("Successfully imported {$importedCount} questions.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error importing questions: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
