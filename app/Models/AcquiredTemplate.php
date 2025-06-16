<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcquiredTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',           // ID пользователя, который получил шаблон
        'user_template_id',  // ID оригинального шаблона пользователя
        'status',            // Статус шаблона (приобретен, активен, использован и т.д.)
        'status_changed_at', // Добавляем поле в fillable
        'folder_id',         // ID папки, в которой находится шаблон
        'acquired_at',       // Добавляем поле acquired_at в fillable
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status_changed_at' => 'datetime', // Добавляем приведение типов для поля
        'acquired_at' => 'datetime', // Добавляем приведение типов для поля acquired_at
    ];

    /**
     * Получить пользователя, которому принадлежит этот полученный шаблон.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить оригинальный шаблон пользователя.
     */
    public function userTemplate()
    {
        return $this->belongsTo(UserTemplate::class, 'user_template_id');
    }
    
    /**
     * Связь с папкой, в которой находится шаблон.
     */
    public function folder()
    {
        return $this->belongsTo(AcquiredTemplateFolder::class, 'folder_id');
    }
}
