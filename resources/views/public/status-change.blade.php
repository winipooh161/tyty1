<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    
    <title>Изменение статуса шаблона | {{ config('app.name') }}</title>
    
    <!-- Стили -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .status-card {
            max-width: 500px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: white;
        }
        
        .status-header {
            padding: 30px;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .success-header {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .error-header {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
        }
        
        .status-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .status-body {
            padding: 30px;
        }
        
        .status-actions {
            padding: 20px 30px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            text-align: center;
        }
        
        .btn-home {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-home:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            color: white;
        }
        
        .verification-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .verification-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            border-bottom: 1px dashed #e9ecef;
            padding-bottom: 8px;
        }
        
        .verification-detail:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="status-card">
        <div class="status-header {{ $success ? 'success-header' : 'error-header' }}">
            @if($success)
                <i class="bi bi-check-circle status-icon"></i>
                <h3>Успешно</h3>
            @else
                <i class="bi bi-x-circle status-icon"></i>
                <h3>Ошибка</h3>
            @endif
        </div>
        
        <div class="status-body">
            <p class="mb-4">{{ $message }}</p>
            
            @if($success && isset($template))
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ $template->name }}</h5>
                        <p class="card-text small text-muted">Статус изменен на: <strong>Использованный</strong></p>
                        <p class="card-text small text-muted">Дата изменения: {{ now()->format('d.m.Y H:i:s') }}</p>
                        
                        <!-- Добавляем информацию о подтверждении -->
                        <div class="verification-info mt-3">
                            <div class="verification-detail">
                                <span>ID шаблона:</span>
                                <strong>{{ $template->id }}</strong>
                            </div>
                            <div class="verification-detail">
                                <span>Пользователь:</span>
                                <strong>{{ Auth::user()->name }}</strong>
                            </div>
                            <div class="verification-detail">
                                <span>Время подтверждения:</span>
                                <strong>{{ now()->format('d.m.Y H:i:s') }}</strong>
                            </div>
                            
                            @if(isset($acquired))
                                <div class="verification-detail">
                                    <span>ID получения:</span>
                                    <strong>{{ $acquired->id }}</strong>
                                </div>
                                <div class="verification-detail">
                                    <span>Получено:</span>
                                    <strong>{{ $acquired->created_at->format('d.m.Y') }}</strong>
                                </div>
                            @endif
                            
                            <div class="text-center mt-3">
                                <i class="bi bi-shield-check text-success me-2"></i>
                                Статус изменен успешно
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            @if(!$success && isset($errorDetails))
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Детали ошибки:</h6>
                    <ul class="mb-0 ps-3 mt-2">
                        @foreach($errorDetails as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        
        <div class="status-actions">
            <a href="{{ route('home') }}" class="btn-home">
                <i class="bi bi-house-door me-1"></i> Вернуться на главную
            </a>
            
            @if($success && isset($template))
                <!-- Добавляем кнопку для просмотра шаблона -->
                <a href="{{ route('public.template', $template->id) }}" class="btn-home ms-2" style="background-color: #6c757d;">
                    <i class="bi bi-eye me-1"></i> Просмотр шаблона
                </a>
            @endif
        </div>
    </div>
    
    <!-- Добавляем скрипт для автоматического перенаправления через 5 секунд в случае успеха -->
    @if($success)
    <script>
        setTimeout(function() {
            window.location.href = "{{ route('home') }}";
        }, 5000);
    </script>
    @endif
</body>
</html>
