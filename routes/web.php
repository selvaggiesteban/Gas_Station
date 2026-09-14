<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\CustomTestController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\TutoringController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\StudyTechniquesController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\MotivationalQuoteController;
use App\Http\Controllers\StudyRoundController;
use App\Http\Controllers\Teacher\TutoringAnswerController;
use App\Http\Controllers\Teacher\EssayGradingController;
use App\Http\Controllers\Teacher\PracticalCaseReviewController;
use App\Http\Controllers\UserQuestionMetadataController;
use App\Http\Controllers\TopicProgressController;
use App\Http\Controllers\SecureContentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ActivityHeartbeatController;
use App\Http\Controllers\ZoomController;
use App\Http\Controllers\StudySessionController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\BlockController as AdminBlockController;
use App\Http\Controllers\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\TestController as AdminTestController;
use App\Http\Controllers\Admin\MetricsController as AdminMetricsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserCourseAccessController as AdminUserCourseAccessController;
use App\Http\Controllers\WeeklyChallengeController;
use Illuminate\Support\Facades\Route;

// --- ROOT & REDIRECTS ---
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('tienda.inicio');
});

// --- PUBLIC STORE ---
Route::get('/inicio', [ShopController::class, 'landing'])->name('tienda.inicio');
Route::get('/cursos/catalogo', [ShopController::class, 'catalog'])->name('cursos.catalogo');
Route::get('/cursos/{course:slug}', [ShopController::class, 'courseDetail'])->name('curso.detalles');
Route::get('/carrito', [ShopController::class, 'cart'])->name('carrito.ver');
Route::post('/carrito/add/{product}', [ShopController::class, 'addToCart'])->name('carrito.añadir');
Route::post('/carrito/remove/{productId}', [ShopController::class, 'removeFromCart'])->name('carrito.eliminar');
Route::get('/checkout', [ShopController::class, 'checkout'])->name('pago.confirmar');
Route::post('/checkout/process', [ShopController::class, 'processCheckout'])->name('pago.procesar');

Route::get('/pago/exitoso/tienda', [ShopController::class, 'success'])->name('shop.payment.success');
Route::get('/pago/fallido/tienda', [ShopController::class, 'failure'])->name('shop.payment.failure');

// --- PUBLIC LEGAL ---
Route::get('/terminos-y-condiciones', function() { return view('legal.terms'); })->name('legal.terms');
Route::get('/politica-privacidad', function() { return view('legal.privacy'); })->name('legal.privacy');
Route::get('/cookies', function() { return view('legal.cookies'); })->name('legal.cookies');

// --- AUTH ---
Route::middleware('auth')->group(function () {
    // Dashboards
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/panel', [DashboardController::class, 'index'])->name('panel.alias');

    // Profile
    Route::get('/perfil/editar', [ProfileController::class, 'edit'])->name('perfil.editar');
    Route::patch('/perfil/update', [ProfileController::class, 'update'])->name('perfil.actualizar');
    Route::delete('/perfil/delete', [ProfileController::class, 'destroy'])->name('perfil.eliminar');

    // Courses & Topics
    Route::get('/mis-cursos', [CourseController::class, 'index'])->name('cursos.mis-cursos');
    Route::get('/mis-cursos/{course}', [CourseController::class, 'show'])->name('cursos.ver');
    Route::get('/temas/{topic}', [\App\Http\Controllers\TopicController::class, 'show'])->name('temas.ver');
    Route::post('/temas/{topic}/completar', [TopicProgressController::class, 'store'])->name('temas.completar');

    // Exams
    Route::get('/examenes-personalizados', [CustomTestController::class, 'index'])->name('examenes-personalizados.lista');
    Route::get('/examenes-personalizados/crear', [CustomTestController::class, 'create'])->name('examenes-personalizados.crear');
    Route::get('/examenes-personalizados/{customTest}', [CustomTestController::class, 'show'])->name('examenes-personalizados.ver');
    Route::get('/examenes-personalizados/{customTest}/iniciar', [CustomTestController::class, 'start'])->name('examenes-personalizados.iniciar');
    Route::post('/examenes-personalizados/{customTest}/enviar', [CustomTestController::class, 'submit'])->name('examenes-personalizados.enviar');
    Route::get('/examenes-personalizados/{customTest}/resultado', [CustomTestController::class, 'result'])->name('examenes-personalizados.resultado');
    Route::post('/examenes-personalizados/guardar', [CustomTestController::class, 'store'])->name('examenes-personalizados.guardar');
    Route::delete('/examenes-personalizados/{customTest}', [CustomTestController::class, 'destroy'])->name('examenes-personalizados.eliminar');

    // Standard tests list route
    Route::get('/examenes', [TestController::class, 'index'])->name('examenes.lista');
    Route::get('/examenes/{test}', [TestController::class, 'show'])->name('examenes.presentar');
    Route::post('/examenes/{test}/enviar', [TestController::class, 'submit'])->name('examenes.enviar');
    Route::get('/examenes/{test}/resultado', [TestController::class, 'result'])->name('examenes.resultado');
    Route::get('/examenes/{test}/pendiente', [TestController::class, 'pending'])->name('examenes.pendiente');

    // Schedule
    Route::get('/cronogramas/oficial', [ScheduleController::class, 'official'])->name('cronograma.oficial');
    Route::get('/cronogramas/personal', [ScheduleController::class, 'personal'])->name('cronograma.personal');
    Route::get('/schedule/personal', [ScheduleController::class, 'personal'])->name('schedule.personal');
    Route::post('/cronograma/store', [ScheduleController::class, 'store'])->name('cronograma.store');
    Route::post('/cronograma/update', [ScheduleController::class, 'update'])->name('cronograma.update');
    Route::post('/cronograma/reordenar', [ScheduleController::class, 'reorder'])->name('cronograma.reordenar');
    Route::get('/cronograma/{schedule}/estudiar', [ScheduleController::class, 'study'])->name('cronograma.estudiar');
    Route::delete('/cronograma/{schedule}', [ScheduleController::class, 'destroy'])->name('cronograma.destroy');

    // Study Sessions / Pomodoro
    Route::post('/estudio/iniciar', [StudySessionController::class, 'start'])->name('estudio.iniciar');
    Route::post('/estudio/cancelar', [StudySessionController::class, 'cancel'])->name('estudio.cancelar');

    // Battles
    Route::get('/batallas', [BattleController::class, 'index'])->name('batallas.lista');
    Route::get('/batallas/nueva', [BattleController::class, 'create'])->name('batallas.nueva');
    Route::get('/batallas/crear', [BattleController::class, 'create'])->name('battles.create');
    Route::get('/batallas/index', [BattleController::class, 'index'])->name('battles.index');
    Route::get('/batallas/{battle}', [BattleController::class, 'show'])->name('batallas.ver');
    Route::get('/batallas/ver/{battle}', [BattleController::class, 'show'])->name('battles.show');
    Route::get('/batallas/{battle}/play', [BattleController::class, 'play'])->name('battles.play');
    Route::post('/batallas/store', [BattleController::class, 'store'])->name('batallas.enviar');
    Route::post('/batallas/enviar', [BattleController::class, 'store'])->name('battles.store');
    Route::post('/batallas/{battle}/aceptar', [BattleController::class, 'accept'])->name('batallas.aceptar');
    Route::post('/batallas/{battle}/accept', [BattleController::class, 'accept'])->name('battles.accept');
    Route::post('/batallas/{battle}/score', [BattleController::class, 'submitScore'])->name('battles.score');
    Route::post('/batallas/matchmake', [BattleController::class, 'findOpponent'])->name('battles.matchmake');

    // Avatar & Classification
    Route::get('/avatar', [AvatarController::class, 'index'])->name('avatar.personalizar');
    Route::post('/avatar/actualizar', [AvatarController::class, 'upload'])->name('avatar.actualizar');
    Route::post('/avatar/subir', [AvatarController::class, 'upload'])->name('avatar.subir');
    Route::post('/avatar/set-gender', [AvatarController::class, 'setGender'])->name('avatar.set-gender');
    Route::get('/clasificacion', [LeaderboardController::class, 'index'])->name('clasificacion.ver');

    // Tutoring
    Route::get('/tutorias', [TutoringController::class, 'index'])->name('tutorias.lista');
    Route::get('/tutorias/ver/{question}', [TutoringController::class, 'show'])->name('tutorias.ver');
    Route::post('/tutorias/preguntar', [TutoringController::class, 'store'])->name('tutorias.preguntar');
    Route::post('/tutorias/{question}/resolver', [TutoringController::class, 'resolve'])->name('tutorias.resolver');

    // Progress
    Route::get('/progreso', [ProgressController::class, 'index'])->name('progreso.ver');
    Route::get('/progreso/descargar', [ProgressController::class, 'exportPdf'])->name('progreso.descargar-pdf');
    Route::get('/certificates/download', [ProgressController::class, 'exportPdf'])->name('certificates.download');
    Route::get('/tecnicas-estudio', [StudyTechniquesController::class, 'index'])->name('tecnicas-estudio.ver');

    // Subscription & Payments
    Route::get('/suscripcion', [SubscriptionController::class, 'index'])->name('suscripcion.gestionar');
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/suscripcion/suscribirse', [SubscriptionController::class, 'index'])->name('suscripcion.suscribirse');
    Route::get('/pago/{plan}', [PaymentController::class, 'show'])->name('pago.mostrar');
    Route::get('/pago/exitoso', [PaymentController::class, 'success'])->name('pago.exitoso');
    Route::get('/pago/fallido', [PaymentController::class, 'failure'])->name('pago.fallido');

    // Invoices
    Route::get('/mis-facturas', [InvoiceController::class, 'index'])->name('facturas.mis-facturas');
    Route::get('/facturas/ver/{invoice}', [InvoiceController::class, 'show'])->name('facturas.ver');
    Route::get('/invoices/show/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/facturas/descargar/{invoice}', [InvoiceController::class, 'download'])->name('facturas.descargar');
    Route::get('/invoices/download/{invoice}', [InvoiceController::class, 'download'])->name('invoices.download');

    // Engagement
    Route::get('/frases/aleatoria', [MotivationalQuoteController::class, 'getRandom'])->name('frases.aleatoria');
    Route::post('/rondas-estudio/siguiente', [StudyRoundController::class, 'startNewRound'])->name('rondas-estudio.siguiente');
    Route::post('/preguntas/marcar-duda', [UserQuestionMetadataController::class, 'toggleDoubt'])->name('preguntas.marcar-duda');
    Route::post('/preguntas/marcar-favorito', [UserQuestionMetadataController::class, 'toggleFavorite'])->name('preguntas.marcar-favorito');
    Route::post('/questions/toggle-doubt', [UserQuestionMetadataController::class, 'toggleDoubt'])->name('questions.toggle-doubt');
    Route::post('/questions/toggle-favorite', [UserQuestionMetadataController::class, 'toggleFavorite'])->name('questions.toggle-favorite');
    Route::post('/telemetria/heartbeat', [ActivityHeartbeatController::class, 'ping'])->name('telemetria.heartbeat');

    // Notifications
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notificaciones.lista');
    Route::post('/notificaciones/{id}/marcar-leida', [NotificationController::class, 'markAsRead'])->name('notificaciones.marcar-leida');
    Route::post('/notificaciones/marcar-todas-leidas', [NotificationController::class, 'markAllAsRead'])->name('notificaciones.marcar-todas');

    // Secure Content
    Route::get('/seguro/temas/{topic}/pdf', [SecureContentController::class, 'getSignedPdfUrl'])->name('seguro.pdf.url');
    Route::get('/seguro/descargar/{topic}/pdf', [SecureContentController::class, 'downloadPdf'])->name('seguro.pdf.descargar');

    // Anuncios
    Route::get('/anuncios', [AnnouncementController::class, 'index'])->name('anuncios.lista');
    Route::get('/anuncios/ver/{announcement}', [AnnouncementController::class, 'show'])->name('anuncios.ver');

    // Weekly Challenges
    Route::get('/retos-semanales', [WeeklyChallengeController::class, 'index'])->name('weekly-challenges.index');
    Route::get('/retos/actual', [WeeklyChallengeController::class, 'index'])->name('retos.actual');

    // Teacher
    Route::prefix('profesor')->name('teacher.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/tutorias/pendientes', [TutoringAnswerController::class, 'pending'])->name('tutoring.pending');
        Route::get('/tutorias/ver/{question}', [TutoringAnswerController::class, 'show'])->name('tutoring.show');
        Route::post('/tutorias/responder/{question}', [TutoringAnswerController::class, 'store'])->name('tutoring.answer');

        Route::get('/ensayos', [EssayGradingController::class, 'index'])->name('essays.index');
        Route::post('/ensayos/calificar/{submission}', [EssayGradingController::class, 'grade'])->name('essays.grade');

        Route::get('/casos-practicos', [PracticalCaseReviewController::class, 'index'])->name('practical-cases.index');
        Route::get('/casos-practicos/ver/{submission}', [PracticalCaseReviewController::class, 'show'])->name('practical-cases.show');
        Route::post('/casos-practicos/revisar/{submission}', [PracticalCaseReviewController::class, 'review'])->name('practical-cases.review');
    });

    // Content Manager
    Route::prefix('gestor-contenido')->name('content-manager.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('anuncios', \App\Http\Controllers\ContentManager\AnnouncementController::class)->names([
            'index' => 'announcements.index',
            'create' => 'announcements.create',
            'store' => 'announcements.store',
            'show' => 'announcements.show',
            'edit' => 'announcements.edit',
            'update' => 'announcements.update',
            'destroy' => 'announcements.destroy',
        ])->parameters(['anuncios' => 'announcement']);

        Route::resource('cronogramas', \App\Http\Controllers\ContentManager\AcademyScheduleController::class)->names([
            'index' => 'schedules.index',
            'create' => 'schedules.create',
            'store' => 'schedules.store',
            'show' => 'schedules.show',
            'edit' => 'schedules.edit',
            'update' => 'schedules.update',
            'destroy' => 'schedules.destroy',
        ])->parameters(['cronogramas' => 'schedule']);

        Route::resource('impugnaciones', \App\Http\Controllers\ContentManager\QuestionChallengeController::class)->names([
            'index' => 'question-challenges.index',
            'show' => 'question-challenges.show',
            'destroy' => 'question-challenges.destroy',
        ])->parameters(['impugnaciones' => 'challenge']);

        Route::resource('desafios', \App\Http\Controllers\WeeklyChallengeController::class)->names([
            'index' => 'desafios.lista',
            'store' => 'desafios.store',
            'update' => 'desafios.update',
            'destroy' => 'desafios.destroy',
            'show' => 'desafios.show',
        ])->parameters(['desafios' => 'challenge']);

        Route::post('/cronogramas/{schedule}/sincronizar', [\App\Http\Controllers\ContentManager\AcademyScheduleController::class, 'syncToStudents'])->name('cronogramas.sync');
    });

    // --- HEURISTIC LEGACY SYNC (Outside name-groups to avoid prefixing) ---
    
    // Gestor Contenido Spanish Legacy
    Route::get('/gestor-contenido/anuncios-lista', [\App\Http\Controllers\ContentManager\AnnouncementController::class, 'index'])->name('gestor-contenido.anuncios.lista');
    Route::get('/gestor-contenido/cronogramas-lista', [\App\Http\Controllers\ContentManager\AcademyScheduleController::class, 'index'])->name('gestor-contenido.cronogramas.lista');
    Route::get('/gestor-contenido/desafios-lista', [\App\Http\Controllers\WeeklyChallengeController::class, 'index'])->name('gestor-contenido.desafios.lista');
    Route::post('/gestor-contenido/desafios-guardar', [\App\Http\Controllers\WeeklyChallengeController::class, 'store'])->name('gestor-contenido.desafios.store');
    Route::delete('/gestor-contenido/desafios-eliminar/{challenge}', [\App\Http\Controllers\WeeklyChallengeController::class, 'destroy'])->name('gestor-contenido.desafios.destroy');
    Route::post('/gestor-contenido/desafios-aprobar/{challenge}', [\App\Http\Controllers\WeeklyChallengeController::class, 'participate'])->name('gestor-contenido.desafios.aprobar');
    Route::post('/gestor-contenido/desafios-rechazar/{challenge}', [\App\Http\Controllers\WeeklyChallengeController::class, 'index'])->name('gestor-contenido.desafios.rechazar');
    
    // Content Manager English Legacy
    Route::get('/content-manager/challenges', [\App\Http\Controllers\WeeklyChallengeController::class, 'index'])->name('content-manager.challenges.index');
    Route::get('/content-manager/challenges/show/{challenge}', [\App\Http\Controllers\WeeklyChallengeController::class, 'index'])->name('content-manager.challenges.show');
    Route::post('/content-manager/challenges/approve/{challenge}', [\App\Http\Controllers\WeeklyChallengeController::class, 'participate'])->name('content-manager.challenges.approve');
    Route::post('/content-manager/challenges/reject/{challenge}', [\App\Http\Controllers\WeeklyChallengeController::class, 'index'])->name('content-manager.challenges.reject');

    // Coupons Spanish Legacy
    Route::get('/gestor-contenido/cupones', [CouponController::class, 'adminIndex'])->name('gestor-contenido.cupones.admin-lista');
    Route::get('/gestor-contenido/cupones/crear', [CouponController::class, 'create'])->name('gestor-contenido.cupones.crear');
    Route::post('/gestor-contenido/cupones/guardar', [CouponController::class, 'store'])->name('gestor-contenido.cupones.guardar');
    Route::get('/gestor-contenido/cupones/editar/{coupon}', [CouponController::class, 'index'])->name('gestor-contenido.cupones.editar');
    Route::post('/gestor-contenido/cupones/actualizar/{coupon}', [CouponController::class, 'index'])->name('gestor-contenido.cupones.actualizar');
    Route::delete('/gestor-contenido/cupones/eliminar/{coupon}', [CouponController::class, 'index'])->name('gestor-contenido.cupones.eliminar');

    // Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        // Users
        Route::resource('usuarios', AdminUserController::class)->names('usuarios')->parameters(['usuarios' => 'user']);
        Route::get('/usuarios/actividad/{user}', [AdminUserController::class, 'activity'])->name('usuarios.actividad');
        Route::post('/usuarios/suscripcion/{user}', [AdminUserController::class, 'assignSubscription'])->name('usuarios.suscripcion');
        Route::post('/usuarios/perfil/{user}', [AdminUserController::class, 'assignProfile'])->name('usuarios.perfil');

        // N2: Cursos suscritos del usuario + bloqueo manual
        Route::get('/usuarios/{user}/cursos', [AdminUserCourseAccessController::class, 'index'])->name('usuarios.cursos');
        Route::post('/usuarios/{user}/cursos/{subscription}/bloquear', [AdminUserCourseAccessController::class, 'toggleBlock'])->name('usuarios.cursos.bloquear');

        // Courses
        Route::resource('cursos', AdminCourseController::class)->names('cursos')->parameters(['cursos' => 'course']);
        Route::post('/cursos/{course}/duplicar', [AdminCourseController::class, 'duplicate'])->name('cursos.duplicar');

        // Nested Structure Routes
        Route::post('/cursos/{course}/bloques', [AdminBlockController::class, 'store'])->name('cursos.bloques.store');
        Route::post('/bloques/{block}/temas', [AdminTopicController::class, 'store'])->name('bloques.temas.store');
        Route::post('/cursos/{course}/examenes', [AdminTestController::class, 'store'])->name('cursos.examenes.store');
        Route::post('/examenes/{test}/preguntas', [AdminQuestionController::class, 'store'])->name('examenes.preguntas.store');

        // Structure Resources
        Route::resource('bloques', AdminBlockController::class)->names('bloques')->parameters(['bloques' => 'block']);
        Route::resource('temas', AdminTopicController::class)->names('temas')->parameters(['temas' => 'topic']);
        Route::resource('preguntas', AdminQuestionController::class)->names('preguntas')->parameters(['preguntas' => 'question']);
        Route::resource('examenes', AdminTestController::class)->names('examenes')->parameters(['examenes' => 'test']);

        // Admin Structure Custom Routes
        Route::post('/bloques/reordenar', [AdminBlockController::class, 'reorder'])->name('bloques.reordenar');
        Route::post('/temas/reordenar', [AdminTopicController::class, 'reorder'])->name('temas.reordenar');
        Route::post('/preguntas/reordenar', [AdminQuestionController::class, 'reorder'])->name('preguntas.reordenar');

        Route::get('/temas/{topic}/versiones', [AdminTopicController::class, 'versions'])->name('temas.versiones');
        Route::post('/temas/{topic}/versiones', [AdminTopicController::class, 'storeVersion'])->name('temas.versiones.store');
        Route::post('/temas/{topic}/subir', [AdminTopicController::class, 'uploadMaterial'])->name('temas.subir');
        Route::delete('/recursos/{resource}', [AdminTopicController::class, 'deleteResource'])->name('recursos.eliminar');
        Route::delete('/versiones/{version}', [AdminTopicController::class, 'deleteVersion'])->name('versiones.eliminar');
        Route::post('/versiones/{version}/establecer-actual', [AdminTopicController::class, 'setVersionCurrent'])->name('versiones.establecer-actual');

        // Sync for admin themes versions store
        Route::post('/temas/{topic}/versions/store', [AdminTopicController::class, 'storeVersion'])->name('admin.topics.versions.store');

        Route::get('/metricas', [AdminMetricsController::class, 'index'])->name('metricas.panel');
        Route::get('/retos', [WeeklyChallengeController::class, 'index'])->name('retos.index');
        Route::get('/pedidos', [AdminMetricsController::class, 'index'])->name('pedidos');
        Route::get('/configuracion', [AdminMetricsController::class, 'index'])->name('configuracion');
    });

    // Zoom
    Route::get('/zoom/clases', [ZoomController::class, 'index'])->name('zoom.clases');
    Route::get('/zoom/esta-semana', [ZoomController::class, 'index'])->name('zoom.esta-semana');
    Route::get('/zoom/clases/ver/{meeting}', [ZoomController::class, 'show'])->name('zoom.ver-clase');
    Route::get('/zoom/clases/entrar/{meeting}', [ZoomController::class, 'join'])->name('zoom.entrar');
    Route::get('/zoom/clases/crear', [ZoomController::class, 'create'])->name('zoom.crear');
    Route::post('/zoom/clases/guardar', [ZoomController::class, 'store'])->name('zoom.guardar');
    Route::get('/zoom/clases/iniciar/{meeting}', [ZoomController::class, 'start'])->name('zoom.iniciar');
    Route::post('/zoom/clases/cancelar/{meeting}', [ZoomController::class, 'cancel'])->name('zoom.cancelar');
    Route::post('/zoom/clases/finalizar/{meeting}', [ZoomController::class, 'finish'])->name('zoom.finalizar');
});

// Social Auth
Route::get('/autenticacion/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/autenticacion/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

require __DIR__.'/auth.php';
Route::impersonate();
