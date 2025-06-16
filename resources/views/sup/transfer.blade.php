@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-paper-plane text-primary me-2"></i>
                        Перевести SUP
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Информация о балансе -->
                    <div class="alert alert-info">
                        <strong>Ваш текущий баланс:</strong> {{ number_format($balance->balance, 2) }} SUP
                    </div>

                    <form method="POST" action="{{ route('sup.execute-transfer') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="recipient_email" class="form-label">Email получателя</label>
                            <input type="email" 
                                   class="form-control @error('recipient_email') is-invalid @enderror" 
                                   id="recipient_email" 
                                   name="recipient_email" 
                                   value="{{ old('recipient_email') }}" 
                                   required>
                            @error('recipient_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Сумма перевода (SUP)</label>
                            <input type="number" 
                                   class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" 
                                   name="amount" 
                                   value="{{ old('amount') }}" 
                                   min="1" 
                                   max="{{ $balance->balance }}"
                                   step="0.01" 
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Максимум: {{ number_format($balance->balance, 2) }} SUP
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Комментарий (необязательно)</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      maxlength="255">{{ old('description') }}</textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>
                                Отправить перевод
                            </button>
                            <a href="{{ route('sup.index') }}" class="btn btn-secondary">
                                Отмена
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
