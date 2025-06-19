<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateEditorController;
use App\Http\Controllers\UserTemplateController; 
use App\Http\Controllers\SeriesTemplateController;
use App\Http\Controllers\PublicTemplateController;
use App\Http\Controllers\TemplateFolderController;
use App\Http\Controllers\Admin\TemplateCategoryController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\TemplateStatusController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\SupController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('login');
});

Auth::routes();

// Маршруты для авторизации через соц. сети
Route::get('auth/{provider}', [SocialController::class, 'redirect'])->name('social.redirect');
Route::get('auth/{provider}/callback', [SocialController::class, 'callback'])->name('social.callback');

// Публичные маршруты (без авторизации)
Route::get('/template/{id}', [PublicTemplateController::class, 'show'])->name('public.template');

// Маршрут для изменения статуса шаблона через QR-код
Route::get('/template-status/change/{template}/{user}/{acquired}/{timestamp?}', [TemplateStatusController::class, 'changeStatusByUrl'])
    ->name('template.status.change');

// Новые маршруты для работы с сериями шаблонов (перемещаем выше других middleware групп)
Route::middleware('auth')->group(function() {
    Route::post('/series/acquire/{id}', [SeriesTemplateController::class, 'acquire'])->name('series.acquire');
});

// Маршруты для всех типов пользователей
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Маршруты для SUP валюты
Route::middleware(['auth'])->prefix('sup')->name('sup.')->group(function () {
    Route::get('/', [SupController::class, 'index'])->name('index');
    Route::get('/transfer', [SupController::class, 'transfer'])->name('transfer');
    Route::post('/transfer', [SupController::class, 'executeTransfer'])->name('execute-transfer');
    Route::get('/balance', [SupController::class, 'getBalance'])->name('balance');
    
    // Новые маршруты для обработки пополнения SUP
    Route::post('/calculate', [SupController::class, 'calculateSup'])->name('calculate');
    Route::post('/payment', [SupController::class, 'processPayment'])->name('payment');
    Route::get('/progression', [SupController::class, 'getProgressionTable'])->name('progression');
    
    // Административные маршруты для SUP
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', [SupController::class, 'admin'])->name('admin');
        Route::post('/admin/add', [SupController::class, 'adminAdd'])->name('admin.add');
        Route::post('/admin/subtract', [SupController::class, 'adminSubtract'])->name('admin.subtract');
    });
});

// Маршруты для профиля
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    
    // Исправляем маршрут для обновления профиля через AJAX - используем метод update вместо updateAjax
    Route::post('/user/update-profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('user.update-profile');
    Route::post('/user/update-avatar', [App\Http\Controllers\ProfileController::class, 'updateAvatar'])->name('user.update-avatar');
});

// Маршруты для администраторов
Route::prefix('admin')->middleware('role:admin')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.dashboard');
    
    // Маршруты для управления категориями шаблонов
    Route::resource('template-categories', TemplateCategoryController::class, ['as' => 'admin']);
    
    // Маршруты для управления шаблонами
    Route::resource('templates', AdminTemplateController::class, ['as' => 'admin']);
    
    // Маршруты для управления пользователями
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class, ['as' => 'admin']);
});

// Маршруты для клиентов (и администраторов)
Route::prefix('client')->middleware('role:client,admin')->group(function () {
    Route::get('/', [App\Http\Controllers\ClientController::class, 'index'])->name('client.dashboard');
    
    // Маршруты для работы с шаблонами
    Route::get('/templates/categories', [TemplateController::class, 'categories'])->name('client.templates.categories');
    Route::get('/templates/category/{slug}', [TemplateController::class, 'index'])->name('client.templates.index');
    Route::get('/templates/category/{categorySlug}/{templateSlug}', [TemplateController::class, 'show'])->name('client.templates.show');
    
    // Восстанавливаем маршрут для создания нового шаблона
    Route::get('/templates/create-new/{id}', [TemplateController::class, 'createNew'])->name('client.templates.create-new');
    
    Route::post('/templates/editor/{id}/save', [TemplateEditorController::class, 'save'])->name('client.templates.save');
    Route::post('/templates/editor/{id}/save-ajax', [TemplateEditorController::class, 'saveAjax'])->name('client.templates.save-ajax');
    
    // Новый маршрут для обработки отправки форм из шаблонов
    Route::post('/form-submission/{templateId}', [App\Http\Controllers\FormSubmissionController::class, 'submit'])->name('form.submit');
    
    // Исправляем маршрут для управления пользовательскими шаблонами
    Route::get('/my-templates', [UserTemplateController::class, 'index'])->name('user.templates');
    Route::get('/my-templates/{id}', [UserTemplateController::class, 'show'])->name('user.templates.show');
    Route::delete('/my-templates/{id}', [UserTemplateController::class, 'destroy'])->name('user.templates.destroy');
    
    // Новые маршруты для публикации/отмены публикации шаблонов
    Route::post('/my-templates/{id}/publish', [UserTemplateController::class, 'publish'])->name('user.templates.publish');
    Route::post('/my-templates/{id}/unpublish', [UserTemplateController::class, 'unpublish'])->name('user.templates.unpublish');
    
    // Новый маршрут для перемещения шаблона в папку
    Route::post('/my-templates/{id}/move', [UserTemplateController::class, 'moveToFolder'])->name('user.templates.move');
    
    // Маршруты для управления папками шаблонов
    Route::post('/folders', [TemplateFolderController::class, 'store'])->name('client.folders.store');
    Route::put('/folders/{id}', [TemplateFolderController::class, 'update'])->name('client.folders.update');
    Route::delete('/folders/{id}', [TemplateFolderController::class, 'destroy'])->name('client.folders.destroy');
    
    // Маршруты для управления папками полученных шаблонов
    Route::post('/acquired-folders', [App\Http\Controllers\AcquiredTemplateFolderController::class, 'store'])->name('acquired.folders.store');
    Route::put('/acquired-folders/{id}', [App\Http\Controllers\AcquiredTemplateFolderController::class, 'update'])->name('acquired.folders.update');
    Route::delete('/acquired-folders/{id}', [App\Http\Controllers\AcquiredTemplateFolderController::class, 'destroy'])->name('acquired.folders.destroy');
    
    // Маршрут для перемещения полученных шаблонов в папки
    Route::post('/acquired-templates/move', [App\Http\Controllers\AcquiredTemplateController::class, 'moveToFolder'])->name('acquired.templates.move');
});

// Добавляем новый маршрут для просмотра шаблонов другого пользователя
Route::middleware(['auth'])->group(function () {
    Route::get('/users/{userId}/templates', [UserTemplateController::class, 'showUserTemplates'])
        ->name('users.templates');
});

// Маршрут для обновления CSRF-токена
Route::get('/refresh-csrf', function () {
    return response()->json(['token' => csrf_token()]);
})->name('refresh-csrf');

// Маршруты для обычных пользователей
Route::prefix('user')->middleware('role:user')->group(function () {
    Route::get('/', function () {
        return view('user.dashboard');
    })->name('user.dashboard');
    // Другие маршруты для обычных пользователей
});

// Маршруты для редактора медиа с базовым шаблоном
Route::middleware(['auth', 'role:client,admin'])->group(function () {
    Route::get('/media/editor', 'App\Http\Controllers\MediaEditorController@index')->name('media.editor');
    Route::get('/media/editor/{template_id}', 'App\Http\Controllers\MediaEditorController@editForTemplate')->name('media.editor.template');
    Route::post('/media/process', 'App\Http\Controllers\MediaEditorController@processMedia')->name('media.process');
});

// Маршруты для медиа-редактора
Route::middleware(['auth'])->group(function () {
    // Маршрут для отображения редактора
    Route::get('/media/editor/{template?}', [App\Http\Controllers\MediaController::class, 'editor'])
        ->name('media.editor');
    
    // Маршрут для обработки медиафайлов
    Route::post('/media/process', [App\Http\Controllers\MediaController::class, 'process'])
        ->name('media.process');
    
    // Альтернативный маршрут для обработки медиа
    Route::post('/media/process-media', [App\Http\Controllers\MediaEditorController::class, 'processMedia'])
        ->name('media.process-media');
    
    // Маршрут для создания нового шаблона
    Route::get('/templates/create-new/{id}', [App\Http\Controllers\TemplateController::class, 'createNew'])
        ->name('templates.create-new');
    
    // Маршрут для редактора шаблонов
    Route::get('/templates/editor/{id}', [App\Http\Controllers\TemplateController::class, 'edit'])
        ->name('templates.editor');
    
  
});

// Маршруты для шаблонов
Route::middleware(['auth'])->prefix('templates')->group(function () {
    Route::get('/categories', [App\Http\Controllers\TemplateController::class, 'categories'])->name('templates.categories');
    
    // Добавляем или исправляем маршрут для редактора шаблонов
    Route::get('/editor/{id}', [App\Http\Controllers\TemplateEditorController::class, 'edit'])->name('templates.editor');
    Route::get('/create-new/{id}', [App\Http\Controllers\TemplateEditorController::class, 'createNew'])->name('templates.create-new');
    Route::post('/save/{id}', [App\Http\Controllers\TemplateEditorController::class, 'save'])->name('templates.save');
});

// Дополнительно добавляем маршруты для клиентской части
Route::middleware(['auth', 'role:client,admin'])->prefix('client')->name('client.')->group(function () {
    // Шаблоны клиентов
    Route::get('/templates/categories', [App\Http\Controllers\TemplateController::class, 'categories'])->name('templates.categories');
    Route::get('/templates/{category:slug}', [App\Http\Controllers\TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/{category:slug}/{template:slug}', [App\Http\Controllers\TemplateController::class, 'show'])->name('templates.show');
    
    // Создание и редактирование шаблонов
    Route::get('/templates/editor/{id}', [App\Http\Controllers\TemplateEditorController::class, 'edit'])->name('templates.editor');
    Route::get('/templates/create-new/{id}', [App\Http\Controllers\TemplateEditorController::class, 'createNew'])->name('templates.create-new');
    Route::post('/templates/save/{id}', [App\Http\Controllers\TemplateEditorController::class, 'save'])->name('templates.save');
});

if (app()->environment('production')) {
    URL::forceScheme('https');
}

// Добавим также стили для мобильного отображения
Route::middleware(['auth'])->group(function() {
    // Маршрут для проверки мобильной навигации
    Route::get('/check-mobile-nav', function() {
        return response()->json([
            'success' => true,
            'message' => 'Mobile navigation check successful'
        ]);
    })->name('check-mobile-nav');
});