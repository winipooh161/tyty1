@extends('layouts.app')

@section('content')
<div class="media-editor-container fullscreen-editor">
    <!-- Индикатор загрузки -->
    @include('media.media-editor.processing-indicator')

    <!-- Секция загрузки файла -->
    @include('media.media-editor.upload-section')

    <!-- Секция для редактирования изображений -->
    @include('media.media-editor.image-editor')

    <!-- Секция для редактирования видео -->
    @include('media.media-editor.video-editor')

    <!-- Действия -->
    @include('media.media-editor.action-buttons')
</div>

<!-- Передаем ID шаблона, если он был передан -->
@if(isset($template))
<input type="hidden" id="templateId" value="{{ $template->id }}">
@endif
@endsection

@section('styles')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
@include('media.media-editor.styles')
@endsection

@section('scripts')
<script src="{{ asset('js/media-editor.js') }}" defer></script>
@endsection
        