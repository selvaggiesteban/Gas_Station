<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Option;
use App\Models\Test;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\IOFactory;

class ImportQuestionsFromWord extends Command
{
    protected $signature = 'import:word {file} {test_id}';
    protected $description = 'Import questions from a .docx file into a specific test';

    public function handle()
    {
        $filePath = $this->argument('file');
        $testId = $this->argument('test_id');
        $test = Test::find($testId);

        if (!$test) {
            $this->error("Test ID {$testId} not found.");
            return 1;
        }

        if (!file_exists($filePath)) {
            $this->error("File {$filePath} not found.");
            return 1;
        }

        $this->info("Parsing Word file: {$filePath}");

        try {
            $phpWord = IOFactory::load($filePath);
            $sections = $phpWord->getSections();
            
            $currentQuestion = null;
            $options = [];
            $count = 0;

            foreach ($sections as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text = trim($element->getText());
                        if (empty($text)) continue;

                        // Detect Question (starts with digit followed by dot or parenthesis)
                        if (preg_match('/^\d+[\.\)]\s*(.*)/i', $text, $matches)) {
                            // Save previous question if exists
                            if ($currentQuestion) {
                                $this->saveQuestion($test, $currentQuestion, $options);
                                $count++;
                            }

                            $currentQuestion = $matches[1];
                            $options = [];
                        } 
                        // Detect Options (starts with a, b, c, d followed by dot or parenthesis)
                        elseif (preg_match('/^([a-d])[\.\)]\s*(.*)/i', $text, $matches)) {
                            $letter = strtolower($matches[1]);
                            $optionText = $matches[2];
                            
                            // Simple logic: if the text contains "(CORRECTA)" it's the correct one
                            $isCorrect = str_contains($text, '(CORRECTA)') || str_contains($text, '*');
                            $optionText = str_replace(['(CORRECTA)', '*'], '', $optionText);

                            $options[] = [
                                'text' => trim($optionText),
                                'is_correct' => $isCorrect
                            ];
                        }
                    }
                }
            }

            // Save last question
            if ($currentQuestion) {
                $this->saveQuestion($test, $currentQuestion, $options);
                $count++;
            }

            $this->success("Successfully imported {$count} questions.");

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function saveQuestion($test, $text, $options)
    {
        $question = Question::create([
            'test_id' => $test->id,
            'text' => $text,
            'type' => count($options) > 4 ? 'five_options' : 'multiple_choice',
            'points' => 1,
        ]);

        foreach ($options as $index => $opt) {
            Option::create([
                'question_id' => $question->id,
                'text' => $opt['text'],
                'is_correct' => $opt['is_correct'],
                'order' => $index,
            ]);
        }
    }
}
