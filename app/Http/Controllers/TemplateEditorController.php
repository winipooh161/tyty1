<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\UserTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class TemplateEditorController extends Controller
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
     * Показать страницу редактирования шаблона
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // Получаем пользовательский шаблон
        $userTemplate = UserTemplate::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        // Если шаблона нет, перенаправляем
        if (!$userTemplate) {
            return redirect()->route('user.templates')->with('error', 'Шаблон не найден.');
        }

        // Получаем базовый шаблон
        $template = Template::findOrFail($userTemplate->template_id);

        return view('templates.editor', compact('userTemplate', 'template'));
    }

    /**
     * Показать страницу создания нового шаблона
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function createNew($id)
    {
        // Получаем базовый шаблон
        $template = Template::findOrFail($id);
        $is_new_template = true;

        // Проверяем данные о медиа-файле из сессии
        $mediaFile = session('media_editor_file');
        $mediaType = session('media_editor_type');

        return view('templates.editor', compact('template', 'is_new_template', 'mediaFile', 'mediaType'));
    }

    /**
     * Сохранить шаблон
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request, $id)
    {
        $template = Template::findOrFail($id);
        
        // Валидация
        $validated = $request->validate([
            'html_content' => 'required|string',
            'custom_data' => 'nullable|string',
            'user_template_id' => 'nullable|exists:user_templates,id',
            'is_active' => 'boolean',
            'is_series_template' => 'nullable|boolean',
            'series_quantity' => 'nullable|integer|min:1',
            'required_scans' => 'nullable|integer|min:1',
        ]);
        
        try {
            // Получаем путь к обложке из сессии, если была загрузка через медиа-редактор
            $coverPath = null;
            $coverType = null;
            
            if (session()->has('media_editor_file')) {
                $coverPath = session('media_editor_file');
                $coverType = session('media_editor_type');
                
                // Проверка, существует ли файл
                if ($coverPath && !Storage::disk('public')->exists($coverPath)) {
                    \Log::warning("Файл обложки не существует: {$coverPath}");
                    $coverPath = null;
                    $coverType = null;
                } else {
                    \Log::info("Найден файл обложки: {$coverPath}");
                }
            }
            
            // Обработка custom_data с информацией о серии
            $customData = [];
            if (!empty($validated['custom_data'])) {
                $customData = json_decode($validated['custom_data'], true);
            }
            
            // Если есть прямые данные о серии, добавляем их в customData
            if ($request->has('is_series_template')) {
                $isSeries = (bool)$request->input('is_series_template');
                $seriesQuantity = (int)$request->input('series_quantity', 1);
                $requiredScans = (int)$request->input('required_scans', 1);
                
                // Перезаписываем данные о серии в customData
                $customData['is_series'] = $isSeries;
                $customData['series_quantity'] = $seriesQuantity;
                $customData['required_scans'] = $requiredScans;
                
                \Log::info('Series data extracted from request', [
                    'is_series' => $isSeries,
                    'series_quantity' => $seriesQuantity,
                    'required_scans' => $requiredScans
                ]);
            }
            
            // Проверка и логирование данных о серии
            if (isset($customData['is_series']) || isset($customData['series_quantity'])) {
                \Log::info('Series template data detected', [
                    'is_series' => $customData['is_series'] ?? false,
                    'series_quantity' => $customData['series_quantity'] ?? 1,
                    'required_scans' => $customData['required_scans'] ?? 1
                ]);
            }
            
            // Создаем или обновляем пользовательский шаблон
            if (!empty($request->user_template_id)) {
                $userTemplate = UserTemplate::findOrFail($request->user_template_id);
                
                // Проверяем, принадлежит ли шаблон текущему пользователю
                if ($userTemplate->user_id != auth()->id()) {
                    return back()->with('error', 'Вы не можете редактировать этот шаблон');
                }
                
                $userTemplate->html_content = $validated['html_content'];
                $userTemplate->custom_data = $customData;
                
                // Обновляем обложку только если загружена новая
                if ($coverPath) {
                    // Если есть старая обложка и она отличается от новой, удаляем старую
                    if ($userTemplate->cover_path && $userTemplate->cover_path != $coverPath) {
                        Storage::disk('public')->delete($userTemplate->cover_path);
                    }
                    
                    $userTemplate->cover_path = $coverPath;
                    $userTemplate->cover_type = $coverType;
                    
                    // Очищаем сессию
                    session()->forget(['media_editor_file', 'media_editor_type']);
                }
                
                $userTemplate->is_active = $request->input('is_active', true);
                $userTemplate->save();
                
                \Log::info("Шаблон обновлен с данными о серии: ID={$userTemplate->id}, is_series=".
                           ($customData['is_series'] ?? 'false'));
            } else {
                // Создаем новый шаблон для пользователя
                $userTemplate = new UserTemplate();
                $userTemplate->user_id = auth()->id();
                $userTemplate->template_id = $template->id;
                $userTemplate->name = $template->name;
                $userTemplate->html_content = $validated['html_content'];
                $userTemplate->custom_data = $customData;
                
                // Добавляем обложку, если она загружена через медиа-редактор
                if ($coverPath) {
                    $userTemplate->cover_path = $coverPath;
                    $userTemplate->cover_type = $coverType;
                    
                    // Очищаем сессию
                    session()->forget(['media_editor_file', 'media_editor_type']);
                }
                
                $userTemplate->is_active = $request->input('is_active', true);
                $userTemplate->save();
                
                \Log::info("Создан новый шаблон с данными о серии: ID={$userTemplate->id}, is_series=".
                           ($customData['is_series'] ?? 'false').", series_quantity=".
                           ($customData['series_quantity'] ?? '1').", required_scans=".
                           ($customData['required_scans'] ?? '1'));
            }
            
            return redirect()->route('user.templates')->with('status', 'Шаблон успешно сохранен');
        } catch (\Exception $e) {
            \Log::error("Ошибка при сохранении шаблона: " . $e->getMessage());
            return back()->with('error', 'Произошла ошибка при сохранении шаблона: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Сохранить шаблон через AJAX
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAjax(Request $request, $id)
    {
        try {
            $template = Template::findOrFail($id);
            
            $request->validate([
                'html_content' => 'required',
                'custom_data' => 'nullable'
            ]);

            // Проверяем, существует ли уже такой шаблон у пользователя
            $userTemplate = UserTemplate::where('template_id', $template->id)
                ->where('user_id', Auth::id())
                ->first();
                
            // Если это новый шаблон
            if (!$userTemplate) {
                $userTemplate = new UserTemplate();
                $userTemplate->template_id = $template->id;
                $userTemplate->user_id = Auth::id();
                $userTemplate->name = $template->name . ' - Копия';
                $userTemplate->html_content = $request->input('html_content');
                $userTemplate->is_active = true;
            } else {
                $userTemplate->html_content = $request->input('html_content');
            }
            
            // Сохраняем пользовательские данные и проверяем данные о серии
            if ($request->has('custom_data')) {
                $customData = $request->input('custom_data');
                
                // Если данные о серии переданы в виде строки, преобразуем их в массив
                if (is_string($customData)) {
                    $customData = json_decode($customData, true);
                }
                
                // Проверка информации о серии
                if (isset($customData['is_series']) || isset($customData['series_quantity'])) {
                    \Log::info('Series data found in AJAX request', [
                        'is_series' => $customData['is_series'] ?? false,
                        'series_quantity' => $customData['series_quantity'] ?? 1,
                        'required_scans' => $customData['required_scans'] ?? 1
                    ]);
                }
                
                $userTemplate->custom_data = $customData;
            }
            
            // Проверяем, есть ли обложка из медиа-редактора
            if (session()->has('media_editor_file')) {
                // Получаем данные из сессии
                $mediaFile = session('media_editor_file');
                $mediaType = session('media_editor_type');
                
                // Если файл существует
                if (Storage::disk('public')->exists($mediaFile)) {
                    // Определяем относительный путь к файлу для сохранения в БД
                    $relativePath = '/storage/' . $mediaFile;
                    
                    // Сохраняем информацию об обложке
                    $userTemplate->cover_path = $relativePath;
                    $userTemplate->cover_type = $mediaType;
                }
            }
            
            $userTemplate->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Шаблон успешно сохранен',
                'template_id' => $userTemplate->id
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при сохранении шаблона: ' . $e->getMessage()
            ], 500);
        }
    }
}