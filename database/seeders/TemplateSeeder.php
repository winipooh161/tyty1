<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Создание категорий шаблонов
        $categoryIds = [];
        
        $categories = [
            [
                'name' => 'Свадебные',
                'description' => 'Шаблоны для свадебных приглашений и поздравлений'
            ],
            [
                'name' => 'День рождения',
                'description' => 'Шаблоны для поздравлений с днем рождения'
            ],
            [
                'name' => 'Сертификаты',
                'description' => 'Шаблоны сертификатов и дипломов'
            ]
        ];
        
        foreach ($categories as $category) {
            $categoryId = DB::table('template_categories')->insertGetId([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'is_active' => true,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $categoryIds[$category['name']] = $categoryId;
        }
        
        // Создание шаблонов
        $templates = [
            [
                'name' => 'Свадебное приглашение классическое',
                'category_id' => $categoryIds['Свадебные'],
                'html_content' => '<div class="wedding-template classic-theme">
                    <div class="header">
                        <h1 data-editable="title">Приглашение на свадьбу</h1>
                    </div>
                    <div class="content">
                        <p class="names" data-editable="names">Иван & Мария</p>
                        <p class="invitation-text" data-editable="invitation">С радостью приглашаем вас разделить с нами особенный день - день нашей свадьбы!</p>
                        <div class="details">
                            <p class="date" data-editable="date">12 июня 2024 года</p>
                            <p class="time" data-editable="time">16:00</p>
                            <p class="location" data-editable="location">Ресторан "Версаль", ул. Примерная, 123</p>
                        </div>
                        <p class="rsvp" data-editable="rsvp">Просим подтвердить ваше присутствие до 1 июня по телефону: +7 (123) 456-78-90</p>
                    </div>
                </div>',
                'editable_fields' => json_encode([
                    'title' => 'Заголовок приглашения',
                    'names' => 'Имена жениха и невесты',
                    'invitation' => 'Текст приглашения',
                    'date' => 'Дата свадьбы',
                    'time' => 'Время начала',
                    'location' => 'Место проведения',
                    'rsvp' => 'Информация для подтверждения'
                ])
            ],
            [
                'name' => 'Детское приглашение на день рождения',
                'category_id' => $categoryIds['День рождения'],
                'html_content' => '<div class="birthday-template kids-theme">
                    <div class="header">
                        <h1 data-editable="title">День рождения!</h1>
                    </div>
                    <div class="content">
                        <p class="birthday-person" data-editable="birthday-person">Машеньке исполняется 5 лет!</p>
                        <p class="invitation-text" data-editable="invitation">Приглашаем тебя на веселый праздник с играми, тортом и подарками!</p>
                        <div class="details">
                            <p class="date" data-editable="date">20 июля 2024 года</p>
                            <p class="time" data-editable="time">12:00</p>
                            <p class="location" data-editable="location">Детское кафе "Карамелька", ул. Детская, 45</p>
                        </div>
                        <p class="rsvp" data-editable="rsvp">Подтвердите, пожалуйста, присутствие до 15 июля по телефону: +7 (123) 456-78-90</p>
                    </div>
                </div>',
                'editable_fields' => json_encode([
                    'title' => 'Заголовок приглашения',
                    'birthday-person' => 'Информация об имениннике',
                    'invitation' => 'Текст приглашения',
                    'date' => 'Дата дня рождения',
                    'time' => 'Время начала',
                    'location' => 'Место проведения',
                    'rsvp' => 'Информация для подтверждения'
                ])
            ],
            [
                'name' => 'Сертификат достижений',
                'category_id' => $categoryIds['Сертификаты'],
                'html_content' => '<div class="certificate-template achievement">
                    <div class="header">
                        <h1 data-editable="title">Сертификат</h1>
                        <h2 data-editable="subtitle">За выдающиеся достижения</h2>
                    </div>
                    <div class="content">
                        <p class="recipient-name" data-editable="recipient">Иванов Иван Иванович</p>
                        <p class="achievement-text" data-editable="achievement">награждается за проявленное мастерство и выдающиеся результаты в области программирования</p>
                        <div class="details">
                            <p class="date" data-editable="date">15 мая 2024 года</p>
                            <div class="signature-block">
                                <p class="signature" data-editable="signature">Директор А.В. Петров</p>
                            </div>
                        </div>
                    </div>
                </div>',
                'editable_fields' => json_encode([
                    'title' => 'Основной заголовок',
                    'subtitle' => 'Подзаголовок',
                    'recipient' => 'ФИО получателя',
                    'achievement' => 'Текст о достижении',
                    'date' => 'Дата выдачи',
                    'signature' => 'Подпись выдающего лица'
                ])
            ]
        ];
        
        foreach ($templates as $template) {
            DB::table('templates')->insert([
                'name' => $template['name'],
                'slug' => Str::slug($template['name']),
                'description' => 'Пример шаблона ' . $template['name'],
                'template_category_id' => $template['category_id'],
                'html_content' => $template['html_content'],
                'editable_fields' => $template['editable_fields'],
                'is_active' => true,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
