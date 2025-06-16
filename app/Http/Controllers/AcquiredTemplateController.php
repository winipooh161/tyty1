<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcquiredTemplate;
use Illuminate\Support\Facades\Auth;

class AcquiredTemplateController extends Controller
{
    /**
     * Переместить полученный шаблон в выбранную папку
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function moveToFolder(Request $request)
    {
        // Валидация данных запроса
        $request->validate([
            'template_id' => 'required|exists:acquired_templates,id',
            'folder_id' => 'nullable|exists:acquired_template_folders,id'
        ]);
        
        // Находим шаблон и проверяем, что он принадлежит текущему пользователю
        $acquiredTemplate = AcquiredTemplate::where('id', $request->template_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        // Обновляем привязку к папке
        $acquiredTemplate->folder_id = $request->folder_id ?: null;
        $acquiredTemplate->save();
        
        // Возвращаемся на предыдущую страницу с сообщением
        return back()->with('status', 'Шаблон успешно перемещен в выбранную папку.');
    }
}
