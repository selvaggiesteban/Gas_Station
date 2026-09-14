<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\Option;
use App\Models\Question;
use App\Models\PracticalCaseSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    /**
     * Display a listing of available tests for the student.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Simulacros are available to everyone
        $simulacros = Test::where('type', 'simulacro')->where('is_active', true)->get();
        
        // Assigned tests (from courses the user has subscribed to)
        $courseIds = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with('product')
            ->get()
            ->pluck('product.course_id')
            ->filter()
            ->unique();
            
        $assignedTests = Test::whereIn('course_id', $courseIds)
            ->where('type', '!=', 'simulacro')
            ->where('is_active', true)
            ->get();
            
        $recentAttempts = TestAttempt::where('user_id', $user->id)
            ->with('test')
            ->latest()
            ->limit(5)
            ->get();

        return view('tests.index', compact('simulacros', 'assignedTests', 'recentAttempts'));
    }

    public function show(Test $test, Request $request)
    {
        $user = Auth::user();

        // Check if it's a simulacro (free access for all registered users)
        $isSimulacro = strtolower($test->type) === 'simulacro' || str_contains(strtolower($test->title), 'simulacro');

        if (!$isSimulacro) {
            // Check if user has active subscription for the test's course
            $hasAccess = $user->hasRole(['Admin', 'Teacher', 'Content Manager']) ||
                         $user->subscriptions()
                             ->where('status', 'active')
                             ->whereHas('product', function($q) use ($test) {
                                 $q->where('course_id', $test->course_id);
                             })->exists();

            if (!$hasAccess) {
                return redirect()->route('suscripcion.gestionar')
                    ->with('error', 'Necesitas una suscripción activa al curso para acceder a este test.');
            }
        }

        // Check attempt limit
        $attemptStatus = $test->canUserAttempt();

        if (!$attemptStatus['can_attempt'] && !$request->has('only_failed')) {
            return redirect()->back()
                ->with('error', $attemptStatus['message']);
        }

        $query = $test->questions()->with('options');

        // Logic for "Repeat only failed"
        if ($request->has('only_failed')) {
            $lastAttempt = TestAttempt::where('user_id', $user->id)
                ->where('test_id', $test->id)
                ->latest()
                ->first();

            if ($lastAttempt && is_array($lastAttempt->answers)) {
                $failedQuestionIds = [];
                foreach ($lastAttempt->answers as $qId => $data) {
                    $type = $data['type'] ?? 'multiple_choice';
                    if ($type === 'multiple_choice') {
                        $option = Option::find($data['answer']);
                        if (!$option || !$option->is_correct) {
                            $failedQuestionIds[] = $qId;
                        }
                    } elseif ($type === 'fill_blank') {
                        $question = Question::find($qId);
                        if ($question) {
                            $normalizedUser = $this->normalizeText($data['answer'] ?? '');
                            $normalizedCorrect = $this->normalizeText($question->fill_blank_text ?? '');
                            if ($this->textSimilarity($normalizedUser, $normalizedCorrect) < 0.9) {
                                $failedQuestionIds[] = $qId;
                            }
                        }
                    }
                }

                if (empty($failedQuestionIds)) {
                    return redirect()->route('examenes.resultado', $test->id)
                        ->with('info', '¡No tienes preguntas falladas en este test!');
                }

                $query->whereIn('id', $failedQuestionIds);
            }
        }

        $questions = $query->orderBy('display_order', 'asc')->get();
        $isRetry = $request->has('only_failed');

        return view('tests.show', compact('test', 'questions', 'attemptStatus', 'isRetry'));
    }

    public function submit(Request $request, Test $test)
    {
        $answers = $request->input('answers', []); // question_id => option_id
        $practicalCases = $request->input('practical_cases', []); // question_id => answer_text
        $fillBlanks = $request->input('fill_blanks', []); // question_id => answer_text
        $openEnded = $request->input('open_ended', []); // question_id => answer_text

        try {
            // Get questions by type
            $multipleChoiceQuestions = $test->questions()->whereIn('type', ['multiple_choice', 'five_options'])->get();
            $practicalCaseQuestions = $test->questions()->where('type', 'practical_case')->get();
            $fillBlankQuestions = $test->questions()->where('type', 'fill_blank')->get();
            $openEndedQuestions = $test->questions()->where('type', 'open_ended')->get();

            // Calculate multiple choice score
            $correctAnswersScore = 0;
            $penalizationScore = 0;
            foreach ($multipleChoiceQuestions as $question) {
                $optionId = $answers[$question->id] ?? null;
                if ($optionId) {
                    $option = Option::find($optionId);
                    if ($option && $option->is_correct) {
                        $correctAnswersScore += 1;
                    } else {
                        $optionsCount = $question->type === 'five_options' ? 5 : 4;
                        $penalizationScore += (1 / ($optionsCount - 1));
                    }
                }
            }

            // Calculate fill_blank score
            $correctFillBlanks = 0;
            foreach ($fillBlankQuestions as $question) {
                $userAnswer = $fillBlanks[$question->id] ?? '';
                $correctAnswer = $question->fill_blank_text ?? '';

                if (!empty($correctAnswer)) {
                    $normalizedUser = $this->normalizeText($userAnswer);
                    $normalizedCorrect = $this->normalizeText($correctAnswer);
                    if ($this->textSimilarity($normalizedUser, $normalizedCorrect) >= 0.9) {
                        $correctFillBlanks++;
                    }
                }
            }

            // Prepare answers array
            $answersData = [];
            foreach ($answers as $questionId => $optionId) {
                $answersData[$questionId] = ['type' => 'multiple_choice', 'answer' => $optionId];
            }
            foreach ($practicalCases as $questionId => $answerText) {
                $answersData[$questionId] = ['type' => 'practical_case', 'answer' => $answerText];
            }
            foreach ($fillBlanks as $questionId => $answerText) {
                $answersData[$questionId] = ['type' => 'fill_blank', 'answer' => $answerText];
            }
            foreach ($openEnded as $questionId => $answerText) {
                $answersData[$questionId] = ['type' => 'open_ended', 'answer' => $answerText];
            }

            // Create test attempt
            $attempt = TestAttempt::create([
                'user_id' => Auth::id(),
                'test_id' => $test->id,
                'score' => 0, 
                'passed' => false,
                'answers' => $answersData,
                'completed_at' => now(),
            ]);

            // Manual reviews
            foreach ($practicalCaseQuestions as $question) {
                if (isset($practicalCases[$question->id])) {
                    PracticalCaseSubmission::create([
                        'user_id' => Auth::id(),
                        'test_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'answer_text' => $practicalCases[$question->id],
                        'status' => 'submitted',
                    ]);
                }
            }

            foreach ($openEndedQuestions as $question) {
                if (isset($openEnded[$question->id])) {
                    PracticalCaseSubmission::create([
                        'user_id' => Auth::id(),
                        'test_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'answer_text' => $openEnded[$question->id],
                        'status' => 'submitted',
                    ]);
                }
            }

            $autoGradedCount = $multipleChoiceQuestions->count() + $fillBlankQuestions->count();
            $manualReviewCount = $practicalCaseQuestions->count() + $openEndedQuestions->count();

            if ($autoGradedCount > 0) {
                $totalCorrect = $correctAnswersScore + $correctFillBlanks;
                $autoScore = ($totalCorrect / $autoGradedCount) * 100;

                if ($manualReviewCount === 0) {
                    $score = $autoScore;
                    $passed = $score >= $test->passing_score;
                    $attempt->update(['score' => $score, 'passed' => $passed]);

                    $user = Auth::user();
                    $user->increment('total_tests_completed');

                    $pointsToAward = 10;
                    if ($score >= 90) $pointsToAward += 50;
                    elseif ($score >= 80) $pointsToAward += 35;
                    elseif ($score >= 70) $pointsToAward += 20;
                    elseif ($score >= 60) $pointsToAward += 10;
                    elseif ($score >= 50) $pointsToAward += 5;

                    $previousMaxScore = TestAttempt::where('user_id', $user->id)
                        ->where('test_id', $test->id)
                        ->where('id', '!=', $attempt->id)
                        ->max('score') ?? 0;

                    $personalBest = ($score > $previousMaxScore && $previousMaxScore > 0);
                    if ($personalBest) $pointsToAward += 15;

                    app(\App\Services\PointService::class)->award($user, $pointsToAward, 'test', 'TestAttempt', $attempt->id);

                    return redirect()->route('examenes.resultado', $test->id)
                        ->with('last_score', $score)
                        ->with('personal_best', $personalBest)
                        ->with('points_awarded', $pointsToAward);
                }
            }

            return redirect()->route('examenes.resultado', $test->id)
                ->with('info', 'Tus respuestas están pendientes de revisión por un profesor.');

        } catch (\Exception $e) {
            \Log::error("Test submission error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error al procesar el test.')->withInput();
        }
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        return $text;
    }

    private function textSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) return 1.0;
        if (empty($str1) || empty($str2)) return 0.0;
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }

    public function result(Test $test)
    {
        $attempt = TestAttempt::where('user_id', Auth::id())
            ->where('test_id', $test->id)
            ->latest()
            ->first();

        if (!$attempt) {
            return redirect()->route('examenes.presentar', $test)
                ->with('error', 'Aún no has realizado este test.');
        }

        return view('tests.result', compact('test', 'attempt'));
    }

    public function pending(Test $test)
    {
        $attempt = TestAttempt::where('user_id', Auth::id())
            ->where('test_id', $test->id)
            ->latest()
            ->first();

        $submissions = PracticalCaseSubmission::where('test_attempt_id', $attempt->id ?? 0)
            ->with('question')
            ->get();

        return view('tests.pending', compact('test', 'attempt', 'submissions'));
    }
}
