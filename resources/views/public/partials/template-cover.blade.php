<div id="coverPreviewContainer" class="cover-container">
    @if($userTemplate->cover_path)
        @php
            $coverPath = 'storage/'.$userTemplate->cover_path;
            $coverExists = file_exists(public_path($coverPath));
        @endphp
        
        @if($userTemplate->cover_type === 'video' && $coverExists)
            <video id="coverVideo" class="cover-video" autoplay loop muted playsinline>
                <source src="{{ asset($coverPath) }}" type="video/{{ pathinfo($userTemplate->cover_path, PATHINFO_EXTENSION) }}">
                Ваш браузер не поддерживает видео.
            </video>
        @elseif($userTemplate->cover_type === 'image' && $coverExists)
            <img src="{{ asset($coverPath) }}" class="cover-image" alt="{{ $userTemplate->name }}">
        @else
            <div class="cover-fallback">
                <div class="fallback-content">
                    <i class="bi bi-image text-white mb-2" style="font-size: 3rem;"></i>
                    <h3 class="text-white">{{ $userTemplate->name }}</h3>
                </div>
            </div>
        @endif
    @else
        <div class="cover-fallback">
            <div class="fallback-content">
                <i class="bi bi-file-earmark-text text-white mb-2" style="font-size: 3rem;"></i>
                <h3 class="text-white">{{ $userTemplate->name }}</h3>
            </div>
        </div>
    @endif
</div>
  