@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('client.templates.categories') }}">Категории</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
                </ol>
            </nav>
            <h2>{{ $category->name }}</h2>
            <p class="text-muted">{{ $category->description }}</p>
            
            @unless(Auth::user()->isVip())
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Доступен только стандартный шаблон. Для доступа ко всем шаблонам <a href="#" class="alert-link">получите VIP-статус</a>.
                </div>
            @endunless
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        @forelse($templates as $template)
        <div class="col">
            <div class="card h-100  {{ $template->is_default ? 'border border-warning' : '' }}">
                @if($template->preview_image)
                <img src="{{ asset('storage/template_previews/'.$template->preview_image) }}" class="card-img-top template-img" alt="{{ $template->name }}">
                @else
                <div class="card-img-top template-img-placeholder d-flex align-items-center justify-content-center bg-light">
                    <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                </div>
                @endif
                <div class="card-body">
                    <h5 class="card-title">
                        {{ $template->name }}
                        @if($template->is_default)
                            <span class="badge bg-warning text-dark ms-2">Стандартный</span>
                        @endif
                    </h5>
                    <p class="card-text">{{ Str::limit($template->description, 100) }}</p>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('client.templates.show', [$category->slug, $template->slug]) }}" class="btn btn-outline-primary">Просмотр</a>
                        <div>
                            <a href="{{ route('client.templates.create-new', $template->id) }}" class="btn btn-info">
                                <i class="bi bi-plus-square me-1"></i> Создать новый
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">
                В этой категории пока нет доступных шаблонов.
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
