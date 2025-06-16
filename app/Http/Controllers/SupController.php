<?php

namespace App\Http\Controllers;

use App\Models\SupBalance;
use App\Models\SupTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показать баланс и историю транзакций пользователя
     */
    public function index()
    {
        $user = Auth::user();
        $balance = $user->getOrCreateSupBalance();
        
        $transactions = $user->supTransactions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sup.index', compact('balance', 'transactions'));
    }

    /**
     * Показать форму перевода SUP
     */
    public function transfer()
    {
        $balance = Auth::user()->getOrCreateSupBalance();
        return view('sup.transfer', compact('balance'));
    }

    /**
     * Выполнить перевод SUP другому пользователю
     */
    public function executeTransfer(Request $request)
    {
        $request->validate([
            'recipient_email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $sender = Auth::user();
        $recipient = User::where('email', $request->recipient_email)->first();

        // Проверяем, что пользователь не переводит сам себе
        if ($sender->id === $recipient->id) {
            return back()->withErrors(['recipient_email' => 'Нельзя переводить SUP самому себе']);
        }

        $amount = $request->amount;
        $description = $request->description ?? "Перевод от {$sender->name}";

        DB::beginTransaction();

        try {
            $senderBalance = $sender->getOrCreateSupBalance();
            $recipientBalance = $recipient->getOrCreateSupBalance();

            // Проверяем достаточность средств
            if (!$senderBalance->hasSufficientBalance($amount)) {
                throw new \Exception('Недостаточно средств для перевода');
            }

            // Списываем с отправителя
            $senderBalance->subtractSup(
                $amount, 
                "Перевод для {$recipient->name}: {$description}",
                'transfer_out'
            );

            // Зачисляем получателю
            $recipientBalance->addSup(
                $amount,
                "Перевод от {$sender->name}: {$description}",
                'transfer_in'
            );

            DB::commit();

            return redirect()->route('sup.index')
                ->with('status', "Успешно переведено {$amount} SUP пользователю {$recipient->name}");

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    /**
     * API для получения баланса (для AJAX)
     */
    public function getBalance()
    {
        $user = Auth::user();
        $balance = $user->getOrCreateSupBalance();
        
        return response()->json([
            'balance' => (float) $balance->balance,
            'total_earned' => (float) $balance->total_earned,
            'total_spent' => (float) $balance->total_spent,
            'formatted_balance' => number_format($balance->balance, 0),
        ]);
    }

    /**
     * Административная панель для управления SUP (только для админов)
     */
    public function admin()
    {
        $this->checkAdminAccess();
        
        $users = User::with('supBalance')
            ->orderBy('name')
            ->paginate(20);

        return view('sup.admin', compact('users'));
    }

    /**
     * Начислить SUP пользователю (только для админов)
     */
    public function adminAdd(Request $request)
    {
        $this->checkAdminAccess();
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($request->user_id);
        $balance = $user->getOrCreateSupBalance();

        $balance->addSup(
            $request->amount,
            $request->description,
            'bonus'
        );

        return back()->with('status', "Начислено {$request->amount} SUP пользователю {$user->name}");
    }

    /**
     * Списать SUP у пользователя (только для админов)
     */
    public function adminSubtract(Request $request)
    {
        $this->checkAdminAccess();
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($request->user_id);
        $balance = $user->getOrCreateSupBalance();

        try {
            $balance->subtractSup(
                $request->amount,
                $request->description,
                'admin_deduction'
            );

            return back()->with('status', "Списано {$request->amount} SUP у пользователя {$user->name}");
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    /**
     * Проверка прав администратора
     */
    private function checkAdminAccess()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Доступ запрещен');
        }
    }
    
    /**
     * Таблица прогрессии пополнения SUP
     * 
     * @var array
     */
    protected $progressionTable = [
        ['rubles' => 100, 'sup' => 20, 'rate' => 5.00],
        ['rubles' => 200, 'sup' => 42, 'rate' => 4.76],
        ['rubles' => 300, 'sup' => 66, 'rate' => 4.55],
        ['rubles' => 400, 'sup' => 92, 'rate' => 4.35],
        ['rubles' => 500, 'sup' => 120, 'rate' => 4.17],
        ['rubles' => 600, 'sup' => 150, 'rate' => 4.00],
        ['rubles' => 700, 'sup' => 182, 'rate' => 3.85],
        ['rubles' => 800, 'sup' => 216, 'rate' => 3.70],
        ['rubles' => 900, 'sup' => 252, 'rate' => 3.57],
        ['rubles' => 1000, 'sup' => 290, 'rate' => 3.45],
        ['rubles' => 1100, 'sup' => 330, 'rate' => 3.33],
        ['rubles' => 1200, 'sup' => 372, 'rate' => 3.23],
        ['rubles' => 1300, 'sup' => 416, 'rate' => 3.13],
        ['rubles' => 1400, 'sup' => 462, 'rate' => 3.03],
        ['rubles' => 1500, 'sup' => 510, 'rate' => 2.94],
        ['rubles' => 1600, 'sup' => 560, 'rate' => 2.86],
        ['rubles' => 1700, 'sup' => 612, 'rate' => 2.78],
        ['rubles' => 1800, 'sup' => 666, 'rate' => 2.70],
        ['rubles' => 1900, 'sup' => 722, 'rate' => 2.63],
        ['rubles' => 2000, 'sup' => 780, 'rate' => 2.56],
        ['rubles' => 2100, 'sup' => 840, 'rate' => 2.50],
        ['rubles' => 2200, 'sup' => 902, 'rate' => 2.44],
        ['rubles' => 2300, 'sup' => 966, 'rate' => 2.38],
        ['rubles' => 2400, 'sup' => 1032, 'rate' => 2.33],
        ['rubles' => 2500, 'sup' => 1100, 'rate' => 2.27]
    ];

    /**
     * Рассчитать количество SUP на основе введенной суммы в рублях
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateSup(Request $request)
    {
        try {
            // Получаем все данные из запроса для анализа
            $allInput = $request->all();
            
            Log::info('Запрос на расчет SUP получен', [
                'user_id' => Auth::id(),
                'input' => $allInput,
                'content_type' => $request->header('Content-Type'),
            ]);
            
            // Проверяем, есть ли данные в JSON формате
            if ($request->isJson()) {
                $amount = $request->json('amount');
            } 
            // Проверяем данные в обычном формате
            else {
                $amount = $request->input('amount');
            }
            
            // Валидируем данные
            if (!is_numeric($amount) || $amount < 100) {
                Log::warning('Валидация не пройдена', [
                    'amount' => $amount,
                    'is_numeric' => is_numeric($amount)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Сумма должна быть числом и не менее 100',
                    'received' => $amount
                ], 422);
            }
            
            // Находим ближайшую запись в таблице
            $result = $this->calculateSupAmount($amount);
            
            Log::info('Расчет SUP выполнен', [
                'amount' => $amount,
                'result' => $result
            ]);
            
            return response()->json([
                'success' => true,
                'sup' => $result['sup'],
                'rate' => $result['rate'],
                'amount' => $amount
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка при расчете SUP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при расчете: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Обработка запроса на пополнение баланса SUP
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'sup_amount' => 'required|numeric|min:20'
        ]);
        
        $amount = $request->input('amount');
        $supAmount = (int)$request->input('sup_amount');
        
        Log::info('Запрос на пополнение баланса SUP', [
            'user_id' => Auth::id(),
            'amount' => $amount,
            'sup_amount' => $supAmount
        ]);
        
        // Проверяем корректность расчета SUP
        $calculatedResult = $this->calculateSupAmount($amount);
        
        if ($calculatedResult['sup'] != $supAmount) {
            Log::warning('Неверное количество SUP', [
                'calculated' => $calculatedResult['sup'],
                'received' => $supAmount
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Неверное количество SUP'
            ], 400);
        }
        
        try {
            // Проверяем, что тип транзакции 'deposit' допустим
            if (!SupTransaction::isValidType('deposit')) {
                throw new \Exception('Тип транзакции "deposit" не поддерживается. Выполните миграцию базы данных.');
            }
            
            DB::beginTransaction();
            
            $user = Auth::user();
            
            // Получаем баланс пользователя, проверяем его существование явно
            $balance = $user->getOrCreateSupBalance();
            
            if (!$balance) {
                throw new \Exception('Не удалось получить баланс пользователя');
            }
            
            Log::info('Баланс пользователя перед пополнением', [
                'user_id' => $user->id,
                'balance_id' => $balance->id ?? 'null',
                'current_balance' => $balance->balance ?? $balance->amount ?? 0
            ]);
            
            // Начисляем SUP пользователю
            $balance->addSup(
                $supAmount,
                "Пополнение баланса на {$amount} ₽",
                'deposit' // Используем проверенный тип
            );
            
            DB::commit();
            
            // Обновляем данные о балансе после пополнения
            $user = $user->fresh();
            $updatedBalance = $user->getOrCreateSupBalance();
            
            // Определяем поле баланса (может быть balance или amount)
            $balanceField = 'balance';
            if (!isset($updatedBalance->$balanceField) && isset($updatedBalance->amount)) {
                $balanceField = 'amount';
            }
            
            $currentBalance = $updatedBalance->$balanceField ?? 0;
            
            Log::info('Баланс успешно пополнен', [
                'user_id' => $user->id,
                'new_balance' => $currentBalance,
                'field_used' => $balanceField
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Баланс успешно пополнен на {$supAmount} SUP",
                'new_balance' => $currentBalance,
                'balance_field' => $balanceField
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Ошибка при пополнении баланса SUP', [
                'user_id' => Auth::id(),
                'amount' => $amount,
                'sup_amount' => $supAmount,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при пополнении баланса: ' . $e->getMessage(),
                'error_details' => app()->environment('production') ? null : [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }
    
    /**
     * Рассчитывает количество SUP на основе суммы в рублях
     * 
     * @param float $amount
     * @return array
     */
    protected function calculateSupAmount($amount)
    {
        // Если сумма меньше минимальной в таблице
        if ($amount < $this->progressionTable[0]['rubles']) {
            return [
                'sup' => floor($amount * ($this->progressionTable[0]['sup'] / $this->progressionTable[0]['rubles'])),
                'rate' => $this->progressionTable[0]['rate']
            ];
        }
        
        // Если сумма больше максимальной в таблице
        if ($amount > $this->progressionTable[count($this->progressionTable) - 1]['rubles']) {
            $lastEntry = $this->progressionTable[count($this->progressionTable) - 1];
            return [
                'sup' => floor($amount * ($lastEntry['sup'] / $lastEntry['rubles'])),
                'rate' => $lastEntry['rate']
            ];
        }
        
        // Ищем точное совпадение в таблице
        foreach ($this->progressionTable as $entry) {
            if ($entry['rubles'] == $amount) {
                return [
                    'sup' => $entry['sup'],
                    'rate' => $entry['rate']
                ];
            }
        }
        
        // Если нет точного совпадения, выполняем линейную интерполяцию
        for ($i = 0; $i < count($this->progressionTable) - 1; $i++) {
            $lowerEntry = $this->progressionTable[$i];
            $upperEntry = $this->progressionTable[$i + 1];
            
            if ($lowerEntry['rubles'] < $amount && $upperEntry['rubles'] > $amount) {
                $ratio = ($amount - $lowerEntry['rubles']) / ($upperEntry['rubles'] - $lowerEntry['rubles']);
                $sup = floor($lowerEntry['sup'] + $ratio * ($upperEntry['sup'] - $lowerEntry['sup']));
                $rate = round($amount / $sup, 2);
                
                return [
                    'sup' => $sup,
                    'rate' => $rate
                ];
            }
        }
        
        // Если по какой-то причине не нашли соответствие (не должно случиться)
        return [
            'sup' => floor($amount / 5), // Дефолтный курс 5 ₽ за 1 SUP
            'rate' => 5.00
        ];
    }
    
    /**
     * Получить всю таблицу прогрессии (для отладки)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgressionTable()
    {
        return response()->json($this->progressionTable);
    }
}
