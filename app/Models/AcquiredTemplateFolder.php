<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcquiredTemplateFolder extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово назначать.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'color',
        'display_order',
    ];

    /**
     * Получить пользователя, которому принадлежит папка.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить полученные шаблоны в этой папке.
     */
    public function acquiredTemplates()
    {
        return $this->hasMany(AcquiredTemplate::class, 'folder_id');
    }
}
