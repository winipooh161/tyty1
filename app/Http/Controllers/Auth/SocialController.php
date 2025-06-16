<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use GuzzleHttp\Client;

class SocialController extends Controller
{
    /**
     * Перенаправляет на страницу OAuth провайдера
     *
     * @param string $provider
     * @return \Illuminate\Http\Response
     */
    public function redirect($provider)
    {
        if ($provider === 'yandex') {
            // Запрашиваем дополнительные scopes для Яндекса
            // login:info - получение имейла
            // login:avatar - получение аватара
            // login:birthday - получение даты рождения
            // login:email - получение адреса электронной почты
            return Socialite::driver($provider)
                ->scopes(['login:info', 'login:email', 'login:avatar', 'login:birthday'])
                ->redirect();
        }
        
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Обрабатывает ответ от OAuth провайдера
     *
     * @param string $provider
     * @return \Illuminate\Http\Response
     */
    public function callback($provider)
    {
        try {
            $socialiteUser = Socialite::driver($provider)->user();
            
            // Проверяем, есть ли пользователь с таким провайдером и provider_id
            $user = User::where('provider_id', $socialiteUser->getId())
                         ->where('provider', $provider)
                         ->first();
            
            // Если такого пользователя нет, проверяем по email
            if (!$user) {
                $user = User::where('email', $socialiteUser->getEmail())->first();
                
                // Если по email тоже нет, создаем нового пользователя
                if (!$user) {
                    // Получаем дополнительные данные из Яндекса
                    $userData = $this->getYandexUserInfo($socialiteUser->token);
                    
                    $user = User::create([
                        'name' => $userData['real_name'] ?? $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? 'Пользователь',
                        'email' => $socialiteUser->getEmail(),
                        'password' => Hash::make(Str::random(16)),
                        'provider' => $provider,
                        'provider_id' => $socialiteUser->getId(),
                        'email_verified_at' => now(),
                        'role' => 'client',
                        'avatar' => $userData['avatar'] ?? null,
                        'birth_date' => $userData['birthday'] ?? null,
                        'gender' => $userData['sex'] ?? null,
                        'phone' => $userData['default_phone']['number'] ?? null,
                    ]);
                } else {
                    // Если нашли по email, обновляем provider и provider_id
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => $socialiteUser->getId(),
                    ]);
                }
            } else {
                // Обновляем данные существующего пользователя
                $userData = $this->getYandexUserInfo($socialiteUser->token);
                
                $user->update([
                    'name' => $userData['real_name'] ?? $socialiteUser->getName() ?? $user->name,
                    'avatar' => $userData['avatar'] ?? $user->avatar,
                    'birth_date' => $userData['birthday'] ?? $user->birth_date,
                    'gender' => $userData['sex'] ?? $user->gender,
                    'phone' => $userData['default_phone']['number'] ?? $user->phone,
                ]);
            }
            
            // Авторизуем пользователя
            Auth::login($user);
            
            return redirect()->route('home');
            
        } catch (Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Ошибка авторизации через ' . ucfirst($provider) . '. Пожалуйста, попробуйте снова. ' . $e->getMessage());
        }
    }
    
    /**
     * Получает расширенную информацию о пользователе Яндекса
     *
     * @param string $token
     * @return array
     */
    protected function getYandexUserInfo($token)
    {
        try {
            $client = new Client();
            $response = $client->get('https://login.yandex.ru/info', [
                'headers' => [
                    'Authorization' => 'OAuth ' . $token,
                ],
                'http_errors' => false,
            ]);
            
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            return [];
        } catch (Exception $e) {
            return [];
        }
    }
}
