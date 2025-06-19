<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class ProfileController extends Controller
{
    /**
     * Создание экземпляра контроллера.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показать профиль пользователя.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show()
    {
        return view('profile.show', ['user' => Auth::user()]);
    }

    /**
     * Показать форму редактирования профиля пользователя.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    /**
     * Обновление профиля пользователя через AJAX-запрос
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Валидация запроса
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'avatar_base64' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Проверка текущего пароля при изменении пароля
            if ($request->filled('password')) {
                if (!$request->filled('current_password')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Необходимо ввести текущий пароль'
                    ], 422);
                }
                
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Неверный текущий пароль'
                    ], 422);
                }
                
                $user->password = Hash::make($request->password);
            }

            // Обработка аватара - обрабатываем и файл, и base64
            if (($request->hasFile('avatar') || $request->filled('avatar_base64')) && $request->boolean('avatar_updated')) {
                // Удаляем старый аватар если он существует
                if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                    Storage::disk('public')->delete('avatars/' . $user->avatar);
                }
                
                $avatarFileName = null;
                
                // Проверяем наличие директории для аватаров
                if (!Storage::disk('public')->exists('avatars')) {
                    Storage::disk('public')->makeDirectory('avatars');
                }
                
                // Обрабатываем загруженный файл, если он есть
                if ($request->hasFile('avatar')) {
                    $file = $request->file('avatar');
                    $avatarFileName = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('avatars', $avatarFileName, 'public');
                } 
                // Обрабатываем base64, если файл не загружен
                else if ($request->filled('avatar_base64')) {
                    try {
                        $base64Image = $request->input('avatar_base64');
                        
                        // Удаляем префикс base64 если он есть
                        if (strpos($base64Image, 'data:image') !== false) {
                            list(, $base64Image) = explode(';', $base64Image);
                            list(, $base64Image) = explode(',', $base64Image);
                        }
                        
                        $decodedImage = base64_decode($base64Image);
                        
                        if ($decodedImage !== false) {
                            $avatarFileName = time() . '_' . $user->id . '.jpg';
                            $path = 'avatars/' . $avatarFileName;
                            
                            // Сохраняем файл
                            Storage::disk('public')->put($path, $decodedImage);
                        } else {
                            throw new \Exception('Ошибка декодирования base64 изображения');
                        }
                    } catch (\Exception $e) {
                        \Log::error('Ошибка при обработке base64 аватара: ' . $e->getMessage(), [
                            'user_id' => $user->id,
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'Ошибка при обработке изображения: ' . $e->getMessage()
                        ], 422);
                    }
                }
                
                if ($avatarFileName) {
                    $user->avatar = $avatarFileName;
                }
            }

            // Обновление основных данных
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->birth_date = $request->birth_date;
            $user->gender = $request->gender;
            
            $user->save();

            // Возвращаем успешный ответ с данными пользователя
            return response()->json([
                'success' => true,
                'message' => 'Профиль успешно обновлен',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? asset('storage/avatars/' . $user->avatar) : asset('images/default-avatar.jpg'),
                    'phone' => $user->phone,
                    'birth_date' => $user->birth_date,
                    'gender' => $user->gender
                ]
            ]);
        } catch (\Exception $e) {
            // Логируем ошибку
            \Log::error('Ошибка при обновлении профиля: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Возвращаем ошибку
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обновлении профиля: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновление аватара пользователя (отдельный метод для AJAX)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAvatar(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            if ($request->hasFile('avatar')) {
                // Удаляем старый аватар если он существует
                if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                    Storage::disk('public')->delete('avatars/' . $user->avatar);
                }
                
                $avatarFile = $request->file('avatar');
                
                // Создаем директорию если её нет
                if (!Storage::disk('public')->exists('avatars')) {
                    Storage::disk('public')->makeDirectory('avatars');
                }
                
                // Сохраняем файл
                $filename = time() . '_' . $user->id . '.' . $avatarFile->getClientOriginalExtension();
                $path = $avatarFile->storeAs('avatars', $filename, 'public');
                
                $user->avatar = $filename;
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Аватар успешно обновлен',
                    'avatar_url' => asset('storage/avatars/' . $filename)
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Файл аватара не найден'
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка при обновлении аватара: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обновлении аватара'
            ], 500);
        }
    }
}
