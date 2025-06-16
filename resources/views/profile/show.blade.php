@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Профиль</span>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-primary">Редактировать</a>
                </div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="{{ Auth::user()->avatar ? asset('storage/avatars/'.Auth::user()->avatar) : asset('images/default-avatar.jpg') }}" 
                             class="profile-avatar rounded-circle" alt="Аватар">
                        <h4 class="mt-3">{{ Auth::user()->name }}</h4>
                        <p class="text-muted">{{ Auth::user()->email }}</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h5>Роль</h5>
                            <p>{{ ucfirst(Auth::user()->role) }}</p>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <h5>Дата регистрации</h5>
                            <p>{{ Auth::user()->created_at->format('d.m.Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
