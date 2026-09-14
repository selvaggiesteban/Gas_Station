<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Course;
use App\Models\CustomTest;
use App\Models\Question;
use App\Models\Topic;
use App\Services\CustomTestGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomTestController extends Controller
{
    private CustomTestGeneratorService $generatorService;

    public function __construct(CustomTestGeneratorService $generatorService)
    {
        $this->generatorService = $generatorService;
    }

    /**
     * Display list of user's custom tests
     */
    public function index()
    {
        $customTests = CustomTest::forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('custom-tests.index', compact('customTests'));
    }

    /**
     * Show form to create a custom test
     */
    public function create()
    {
        $courses = Course::with(['blocks.topics'])->where('is_active', true)->get();
        
        // Mock data for demo if DB is empty
        if ($courses->isEmpty()) {
            $mockCourse = new Course(['id' => 1, 'title' => 'Cuerpo de Ayudantes de II.PP.']);
            $mockBlock1 = new Block(['id' => 1, 'title' => 'Bloque I: Derecho Administrativo y Función Pública']);
            $mockBlock2 = new Block(['id' => 2, 'title' => 'Bloque II: Derecho Penal']);
            $mockBlock3 = new Block(['id' => 3, 'title' => 'Bloque III: Derecho Penitenciario']);
            
            $mockBlock1->setRelation('topics', collect([
                new Topic(['id' => 1, 'title' => 'Tema 1: La Constitución Española de 1978']),
                new Topic(['id' => 2, 'title' => 'Tema 2: El Tribunal Constitucional']),
                new Topic(['id' => 3, 'title' => 'Tema 12: El personal al servicio de las Administraciones Públicas']),
            ]));
            
            $mockBlock2->setRelation('topics', collect([
                new Topic(['id' => 4, 'title' => 'Tema 1: El Derecho Penal. Concepto y Principios']),
                new Topic(['id' => 5, 'title' => 'Tema 2: El Delito y la Responsabilidad Penal']),
            ]));
            
            $mockBlock3->setRelation('topics', collect([
                new Topic(['id' => 6, 'title' => 'Tema 1: La Ley Orgánica General Penitenciaria']),
                new Topic(['id' => 7, 'title' => 'Tema 2: El Reglamento Penitenciario de 1996']),
            ]));
            
            $mockCourse->setRelation('blocks', collect([$mockBlock1, $mockBlock2, $mockBlock3]));
            $courses = collect([$mockCourse]);
        }

        $questionTypes = [
            'multiple_choice' => 'Opción múltiple (4 opciones)',
            'five_options' => 'Opción múltiple (5 opciones)',
            'fill_blank' => 'Completar espacios',
            'practical_case' => 'Casos prácticos',
        ];

        return view('custom-tests.create', compact('courses', 'questionTypes'));
    }

    /**
     * Store a new custom test configuration
     */
    public function store(Request $request)
    {
        // For demo/simulated data, we relax the validation of IDs against DB
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'blocks' => 'nullable|array',
            'topics' => 'nullable|array',
            'question_types' => 'required|array|min:1',
            'question_types.*' => 'in:multiple_choice,five_options,fill_blank,practical_case,open_ended',
            'difficulty' => 'required|in:easy,medium,hard,mixed',
            'question_count' => 'required|integer|min:5|max:100',
            'time_limit_minutes' => 'required|integer|min:10|max:180',
        ]);

        $customTest = CustomTest::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'blocks' => $validated['blocks'] ?? [],
            'topics' => $validated['topics'] ?? [],
            'question_types' => $validated['question_types'],
            'difficulty' => $validated['difficulty'],
            'question_count' => $validated['question_count'],
            'time_limit_minutes' => $validated['time_limit_minutes'],
            'status' => CustomTest::STATUS_DRAFT,
        ]);

        // Generate the test questions (The service will handle mock generation if needed)
        $result = $this->generatorService->generate($customTest);

        if (!$result['success']) {
            // Simulated success for demo if it fails due to lack of DB questions
            $mockQuestionIds = range(1001, 1001 + $validated['question_count'] - 1);
            $customTest->markAsGenerated($mockQuestionIds);
            
            return redirect()->route('examenes-personalizados.ver', $customTest)
                ->with('success', 'Test personalizado SIMULADO generado con ' . $validated['question_count'] . ' preguntas (Modo Demo).');
        }

        return redirect()->route('examenes-personalizados.ver', $customTest)
            ->with('success', 'Test personalizado generado correctamente con ' . count($result['questions']) . ' preguntas.');
    }

    /**
     * Display the custom test
     */
    public function show(CustomTest $customTest)
    {
        // Authorization check
        if ($customTest->user_id !== Auth::id() && !Auth::user()->hasRole(['Admin', 'Teacher'])) {
            abort(403);
        }

        $customTest->load(['user']);
        $questions = $customTest->questions()->with('options')->get();

        // If no real questions exist (Demo mode), generate mock questions for the summary
        if ($questions->isEmpty() && !empty($customTest->generated_questions)) {
            $questions = collect();
            foreach ($customTest->generated_questions as $index => $id) {
                $q = new Question([
                    'id' => $id,
                    'text' => "¿Pregunta de ejemplo número " . ($index + 1) . " para el test personalizado?",
                    'type' => 'multiple_choice'
                ]);
                
                $options = collect([
                    new \App\Models\Option(['id' => ($id * 10) + 1, 'text' => 'Opción A', 'is_correct' => true]),
                    new \App\Models\Option(['id' => ($id * 10) + 2, 'text' => 'Opción B', 'is_correct' => false]),
                    new \App\Models\Option(['id' => ($id * 10) + 3, 'text' => 'Opción C', 'is_correct' => false]),
                    new \App\Models\Option(['id' => ($id * 10) + 4, 'text' => 'Opción D', 'is_correct' => false]),
                ]);
                
                $q->setRelation('options', $options);
                $questions->push($q);
            }
        }

        return view('custom-tests.show', compact('customTest', 'questions'));
    }

    /**
     * Start taking the custom test
     */
    public function start(CustomTest $customTest)
    {
        if ($customTest->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$customTest->isGenerated()) {
            return redirect()->route('examenes-personalizados.lista')
                ->with('error', 'Este test aún no está listo.');
        }

        if ($customTest->isCompleted()) {
            return redirect()->route('examenes-personalizados.resultado', $customTest);
        }

        $questions = $customTest->questions()->with('options')->get();

        // If no real questions exist (Demo mode), generate mock questions on the fly
        if ($questions->isEmpty() && !empty($customTest->generated_questions)) {
            $questions = collect();
            foreach ($customTest->generated_questions as $index => $id) {
                $q = new Question([
                    'id' => $id,
                    'text' => "¿Pregunta de ejemplo número " . ($index + 1) . " para el test personalizado?",
                    'type' => 'multiple_choice',
                    'points' => 1
                ]);
                
                $options = collect([
                    new \App\Models\Option(['id' => ($id * 10) + 1, 'text' => 'Opción correcta simulada', 'is_correct' => true]),
                    new \App\Models\Option(['id' => ($id * 10) + 2, 'text' => 'Opción incorrecta A', 'is_correct' => false]),
                    new \App\Models\Option(['id' => ($id * 10) + 3, 'text' => 'Opción incorrecta B', 'is_correct' => false]),
                    new \App\Models\Option(['id' => ($id * 10) + 4, 'text' => 'Opción incorrecta C', 'is_correct' => false]),
                ]);
                
                $q->setRelation('options', $options);
                $questions->push($q);
            }
        }

        return view('custom-tests.take', compact('customTest', 'questions'));
    }

    /**
     * Submit answers for the custom test
     */
    public function submit(Request $request, CustomTest $customTest)
    {
        if ($customTest->user_id !== Auth::id()) {
            abort(403);
        }

        if ($customTest->isCompleted()) {
            return redirect()->route('examenes-personalizados.resultado', $customTest);
        }

        $answers = $request->input('answers', []);
        $questions = $customTest->questions()->with('options')->get();

        // If no real questions exist (Demo mode), recreate the same mock questions
        if ($questions->isEmpty() && !empty($customTest->generated_questions)) {
            $questions = collect();
            foreach ($customTest->generated_questions as $id) {
                $q = new Question(['id' => $id, 'type' => 'multiple_choice']);
                $options = collect([
                    new \App\Models\Option(['id' => ($id * 10) + 1, 'is_correct' => true]),
                    new \App\Models\Option(['id' => ($id * 10) + 2, 'is_correct' => false]),
                    new \App\Models\Option(['id' => ($id * 10) + 3, 'is_correct' => false]),
                    new \App\Models\Option(['id' => ($id * 10) + 4, 'is_correct' => false]),
                ]);
                $q->setRelation('options', $options);
                $questions->push($q);
            }
        }

        // Calculate score
        $correctCount = 0;
        foreach ($questions as $question) {
            $selectedOption = $answers[$question->id] ?? null;
            if ($selectedOption) {
                $option = $question->options->where('id', $selectedOption)->first();
                if ($option && $option->is_correct) {
                    $correctCount++;
                }
            }
        }

        $totalQuestions = $questions->count();
        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

        $customTest->markAsCompleted($score);

        return redirect()->route('examenes-personalizados.resultado', $customTest)
            ->with('score', $score);
    }

    /**
     * Show results
     */
    public function result(CustomTest $customTest)
    {
        if ($customTest->user_id !== Auth::id() && !Auth::user()->hasRole(['Admin', 'Teacher'])) {
            abort(403);
        }

        if (!$customTest->isCompleted()) {
            return redirect()->route('examenes-personalizados.ver', $customTest);
        }

        return view('custom-tests.result', compact('customTest'));
    }

    /**
     * Delete a custom test
     */
    public function destroy(CustomTest $customTest)
    {
        if ($customTest->user_id !== Auth::id()) {
            abort(403);
        }

        $customTest->delete();

        return redirect()->route('examenes-personalizados.lista')
            ->with('success', 'Test eliminado correctamente.');
    }

    /**
     * Calculate text similarity
     */
    private function textSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }
}