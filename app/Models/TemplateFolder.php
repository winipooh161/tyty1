<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateFolder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
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
     * Получить шаблоны пользователя в этой папке.
     */
    public function templates()
    {
        return $this->hasMany(UserTemplate::class, 'folder_id');
    }
}
