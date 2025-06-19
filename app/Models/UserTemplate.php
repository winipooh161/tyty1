<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTemplate extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово назначать
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'template_id',
        'folder_id',
        'name',
        'html_content',
        'custom_data',
        'cover_path',
        'cover_type',
        'is_active',
        'is_published',
        'target_user_id'
    ];

    /**
     * Атрибуты, которые должны быть приведены к другим типам
     *
     * @var array
     */
    protected $casts = [
        'custom_data' => 'array',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
    ];

    /**
     * Получить путь к обложке с корректной обработкой пути
     * @return string|null
     */
    public function getCoverPathAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // Если путь уже начинается с 'storage/', оставляем как есть
        if (strpos($value, 'storage/') === 0) {
            return $value;
        }
        
        // Если путь начинается с '/', удаляем его
        if (strpos($value, '/') === 0) {
            $value = substr($value, 1);
        }
        
        // Добавляем префикс 'storage/' если его нет
        if (strpos($value, 'storage/') !== 0 && strpos($value, 'template_covers/') === 0) {
            return 'storage/' . $value;
        }
        
        return $value;
    }

    /**
     * Получить пользователя, которому принадлежит шаблон.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить базовый шаблон, на основе которого создан пользовательский.
     */
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Получить папку, в которой находится шаблон.
     */
    public function folder()
    {
        return $this->belongsTo(TemplateFolder::class);
    }
    
    /**
     * Получить целевого пользователя, если шаблон создан для VIP.
     */
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
    
    /**
     * Проверяет, принадлежит ли шаблон указанному пользователю
     */
    public function belongsToUser($userId)
    {
        return $this->user_id == $userId;
    }
}
