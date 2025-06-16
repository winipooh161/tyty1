@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Личный кабинет клиента</div>

                <div class="card-body">
                    <p>Добро пожаловать в личный кабинет клиента!</p>
                    
                    @if(Auth::user()->role === 'admin')
                        <div class="alert alert-info">
                            Вы просматриваете эту страницу как администратор.
                        </div>
                    @endif
                    
                    <!-- Здесь будет содержимое панели клиента -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
