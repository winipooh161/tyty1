<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('public.partials.template-head')
    
    <!-- Предзагрузка библиотек для шаблона -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/ru.js"></script>
    
    <!-- Скрипт для серийных шаблонов -->
    @if(isset($seriesData) && $seriesData)
    <script>
        // Глобальные данные о серии
        const seriesDataFromServer = @json($seriesData);
    </script>
    @endif
</head>
<body>
    

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: 90%; max-width: 500px;">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: 90%; max-width: 500px;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

  

    @if(isset($userTemplate))
        <div class="content-cover_content">
            @include('public.partials.template-cover')
            @include('public.partials.template-content')
        </div>
        @include('public.partials.template-scripts')
    @else
        <div class="container py-5">
            <div class="alert alert-warning">
                <h4><i class="bi bi-exclamation-triangle"></i> Шаблон не найден</h4>
                <p>Запрашиваемый шаблон не существует или был удален.</p>
                <a href="{{ route('home') }}" class="btn btn-primary mt-3">Вернуться на главную</a>
            </div>
        </div>
    @endif
</body>
</html>



