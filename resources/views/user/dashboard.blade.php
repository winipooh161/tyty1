@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Информационная страница</div>

                <div class="card-body">
                    @auth
                        @if(Auth::user()->role === 'admin')
                            <div class="alert alert-info">
                                Вы просматриваете эту страницу как администратор.
                            </div>
                        @endif
                    @endauth
                    
                    <p>Эта страница доступна всем посетителям.</p>
                    <p>Зарегистрируйтесь, чтобы получить доступ к дополнительным функциям.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
