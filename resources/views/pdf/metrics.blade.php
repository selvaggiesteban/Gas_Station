<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Progreso Academicus</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #f97316; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #f97316; }
        .user-info { margin-bottom: 30px; }
        .section-title { background: #f3f4f6; padding: 10px; font-size: 18px; font-weight: bold; border-left: 5px solid #f97316; margin-bottom: 15px; }
        .stats-grid { display: flex; width: 100%; margin-bottom: 20px; }
        .stat-box { width: 33%; text-align: center; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; margin-right: 10px; }
        .stat-value { font-size: 20px; font-weight: bold; color: #f97316; }
        .stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f9fafb; padding: 10px; text-align: left; font-size: 12px; border-bottom: 1px solid #e5e7eb; }
        td { padding: 10px; font-size: 12px; border-bottom: 1px solid #f3f4f6; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #9ca3af; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ACADEMICUS</div>
        <div style="font-size: 14px; color: #6b7280;">Reporte de Rendimiento Académico</div>
    </div>

    <div class="user-info">
        <div style="font-size: 20px; font-weight: bold;">{{ $user->name }} </div>
        <div style="font-size: 14px; color: #6b7280;">{{ $user->email }} | Rango: {{ $user->getRankName() }} | Nivel {{ $user->level }} </div>
        <div style="font-size: 12px; color: #9ca3af; margin-top: 5px;">Generado el {{ $generated_at }} </div>
    </div>

    <div class="section-title">Resumen de Estadísticas</div>
    <table style="border: none;">
        <tr>
            <td style="width: 25%; border: none;">
                <div class="stat-box" style="width: 100%;">
                    <div class="stat-value">{{ $stats['total_study_time'] }} h</div>
                    <div class="stat-label">Horas de Estudio</div>
                </div>
            </td>
            <td style="width: 25%; border: none;">
                <div class="stat-box" style="width: 100%;">
                    <div class="stat-value">{{ $stats['topics_completed'] }} </div>
                    <div class="stat-label">Temas Vistos</div>
                </div>
            </td>
            <td style="width: 25%; border: none;">
                <div class="stat-box" style="width: 100%;">
                    <div class="stat-value">{{ $test_stats['average_score'] }}%</div>
                    <div class="stat-label">Aciertos (Media)</div>
                </div>
            </td>
            <td style="width: 25%; border: none;">
                <div class="stat-box" style="width: 100%;">
                    <div class="stat-value">Lvl {{ $stats['level'] }} </div>
                    <div class="stat-label">{{ $stats['rank_name'] }} </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Progreso por Bloques</div>
    <table>
        <thead>
            <tr>
                <th>Bloque</th>
                <th>Temas Completados</th>
                <th>Progreso</th>
            </tr>
        </thead>
        <tbody>
            @foreach($block_progress as $block)
                <tr>
                    <td>{{ $block['title'] }} </td>
                    <td>{{ $block['completed_topics'] }} / {{ $block['total_topics'] }} </td>
                    <td>{{ $block['percentage'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Últimos Resultados</div>
    <table>
        <thead>
            <tr>
                <th>Examen</th>
                <th>Fecha</th>
                <th>Nota</th>
                <th>Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($test_stats['recent_attempts'] as $attempt)
                <tr>
                    <td>{{ $attempt->test->title ?? 'N/A' }} </td>
                    <td>{{ $attempt->completed_at->format('d/m/Y') }} </td>
                    <td style="font-weight: bold; color: {{ $attempt->passed ? '#16a34a' : '#dc2626' }}">{{ number_format($attempt->score, 2) }}%</td>
                    <td>{{ $attempt->passed ? 'APROBADO' : 'SUSPENSO' }} </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Este documento es un extracto oficial de tu progreso en la plataforma Academicus.<br>
        &copy; {{ date('Y') }} Academia de Prisiones - Todos los derechos reservados.
    </div>
</body>
</html>
