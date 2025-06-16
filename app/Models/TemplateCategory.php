<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateCategory extends Model
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
        'image',
        'is_active',
        'display_order'
    ];

    /**
     * Получить шаблоны, принадлежащие к этой категории.
     */
    public function templates()
    {
        return $this->hasMany(Template::class);
    }
    
    /**
     * Получить только активные шаблоны этой категории.
     */
    public function activeTemplates()
    {
        return $this->templates()->where('is_active', true)->orderBy('display_order');
    }
}
