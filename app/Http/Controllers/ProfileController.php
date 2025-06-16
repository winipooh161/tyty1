<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\User;

class ProfileController extends Controller
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
     * Show the user profile.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show()
    {
        return view('profile.show', ['user' => Auth::user()]);
    }

    /**
     * Show the form for editing the user's profile.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    /**
     * Update the user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'current_password' => ['nullable', 'required_with:new_password', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    $fail('Текущий пароль указан неверно.');
                }
            }],
            'new_password' => ['nullable', 'min:8', 'required_with:current_password', 'confirmed'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        // Обработка загрузки аватара
        if ($request->hasFile('avatar')) {
            // Удаление старого аватара, если он существует
            if ($user->avatar) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }

            $avatar = $request->file('avatar');
            $filename = time() . '.' . $avatar->getClientOriginalExtension();

            // Создание директории, если она не существует
            if (!Storage::disk('public')->exists('avatars')) {
                Storage::disk('public')->makeDirectory('avatars');
            }

            // Сохранение аватара с изменением размера
            $img = Image::make($avatar->path());
            $img->fit(300, 300)->save(storage_path('app/public/avatars/' . $filename));

            $user->avatar = $filename;
        }

        // Обновление пароля, если он был предоставлен
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return redirect()->route('profile.show')->with('status', 'Профиль успешно обновлен!');
    }

    /**
     * Обновить профиль пользователя через AJAX
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAjax(Request $request)
    {
        $user = Auth::user();
        
        // Валидация основных полей
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Подготовка данных для обновления
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'gender' => $request->gender,
        ];
        
        // Обработка даты рождения
        if ($request->filled('birth_date')) {
            $userData['birth_date'] = Carbon::parse($request->birth_date)->format('Y-m-d');
        }
        
        // Обработка изменения пароля
        if ($request->filled('current_password') && $request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['current_password' => ['Текущий пароль указан неверно']]
                ], 422);
            }
            
            $userData['password'] = Hash::make($request->password);
        }
        
        // Обновляем данные пользователя
        $user->update($userData);
        
        // Обработка аватара, если был загружен
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $filename = time() . '.' . $avatar->getClientOriginalExtension();
            
            // Удаляем старый аватар, если существует
            if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }
            
            // Сохраняем новый аватар
            $avatar->storeAs('avatars', $filename, 'public');
            $user->avatar = $filename;
            $user->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Профиль успешно обновлен',
            'user' => $user
        ]);
    }
    
    /**
     * Обработка загрузки аватара
     */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Ошибка валидации файла'], 422);
        }

        $user = Auth::user();

        // Удаляем старый аватар если он есть
        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        // Генерируем уникальное имя файла
        $fileName = time() . '.' . $request->avatar->extension();
        
        // Сохраняем файл в storage/app/public/avatars
        $request->avatar->storeAs('avatars', $fileName, 'public');
        
        // Обновляем информацию в БД
        $user->avatar = $fileName;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Аватар успешно обновлен',
            'avatar_url' => asset('storage/avatars/' . $user->avatar)
        ]);
    }
}
