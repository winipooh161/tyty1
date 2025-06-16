@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Баланс SUP -->
            <div class="card mb-4 sup-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-coins text-warning me-2"></i>
                        Баланс SUP
                    </h5>
                    <div class="btn-group">
                        <a href="{{ route('sup.transfer') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane me-1"></i>
                            Перевести
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <h3 class="text-primary mb-1 sup-balance-display">{{ number_format($balance->balance, 0) }}</h3>
                                <small class="text-muted">Текущий баланс</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <h4 class="text-success mb-1">{{ number_format($balance->total_earned, 0) }}</h4>
                                <small class="text-muted">Всего заработано</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <h4 class="text-danger mb-1">{{ number_format($balance->total_spent, 0) }}</h4>
                                <small class="text-muted">Всего потрачено</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- История транзакций -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        История транзакций
                    </h5>
                </div>
                <div class="card-body">
                    @if($transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Описание</th>
                                        <th>Тип</th>
                                        <th class="text-end">Сумма</th>
                                        <th class="text-end">Баланс после</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                        <tr class="sup-transaction {{ $transaction->amount > 0 ? 'earned' : 'spent' }}">
                                            <td>
                                                <small>{{ $transaction->created_at->format('d.m.Y H:i') }}</small>
                                            </td>
                                            <td>{{ $transaction->description }}</td>
                                            <td>
                                                <span class="badge bg-{{ $transaction->type_color }}">
                                                    <i class="fas fa-{{ $transaction->type_icon }} me-1"></i>
                                                    {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $transaction->amount > 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 0) }} SUP
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <small class="text-muted">{{ number_format($transaction->balance_after, 0) }} SUP</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{ $transactions->links() }}
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">У вас пока нет транзакций</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
