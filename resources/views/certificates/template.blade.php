<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado - {{ $courseName }} </title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .certificate {
            width: 1000px;
            height: 700px;
            background: white;
            border: 20px solid #fbbf24;
            border-radius: 20px;
            padding: 60px;
            box-sizing: border-box;
            text-align: center;
            position: relative;
        }
        .certificate::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px solid #fbbf24;
            border-radius: 10px;
            pointer-events: none;
        }
        .logo {
            font-size: 48px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 20px;
        }
        .title {
            font-size: 42px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        .recipient {
            font-size: 36px;
            color: #4f46e5;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .course {
            font-size: 28px;
            color: #374151;
            margin-bottom: 40px;
        }
        .date {
            font-size: 20px;
            color: #6b7280;
            margin-bottom: 60px;
        }
        .signature {
            border-top: 2px solid #374151;
            display: inline-block;
            padding-top: 10px;
            margin-top: 40px;
            font-size: 18px;
            color: #374151;
        }
        .certificate-id {
            position: absolute;
            bottom: 30px;
            right: 40px;
            font-size: 14px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="logo">{{ __('ACADEMICUS') }} </div>
        <div class="title">{{ __('Certificado de Finalización') }} </div>
        <div style="font-size: 22px; color: #4b5563; margin-bottom: 30px;">{{ __('Se otorga el presente certificado a:') }} </div>
        <div class="recipient">{{ $studentName }} </div>
        <div style="font-size: 22px; color: #4b5563; margin-bottom: 20px;">{{ __('Por haber completado exitosamente el curso:') }} </div>
        <div class="course">{{ $courseName }} </div>
        <div class="date">Fecha de finalización: {{ $completionDate }} </div>
        <div class="signature">{{ __('Director de Academicus') }} </div>
        <div class="certificate-id">ID: {{ $certificateId }} </div>
    </div>
</body>
</html>
