<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'template_category_id',
        'html_content',
        'editable_fields',
        'preview_image',
        'is_active',
        'is_default',
        'display_order',
        'target_user_id', // Добавляем поле для хранения ID VIP-пользователя
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'editable_fields' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Получить категорию, к которой принадлежит этот шаблон.
     */
    public function category()
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id');
    }
    
    /**
     * Получить пользователя, для которого создан шаблон.
     */
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Получить пользовательские шаблоны, основанные на этом шаблоне.
     */
    public function userTemplates()
    {
        return $this->hasMany(UserTemplate::class);
    }
}
