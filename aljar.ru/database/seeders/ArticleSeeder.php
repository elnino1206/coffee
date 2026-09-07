<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Admin;
use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Статьи журнала.
 *
 * Материалы перенесены из BlogController, где лежали списком до
 * появления таблицы, вместе с датами.
 *
 * Тексты двух статей заказчик ещё не прислал, но они остаются
 * опубликованными: страница статьи для такого случая показывает анонс и
 * пометку «ждём от редакции». Спрятать их в черновики значило бы
 * поменять поведение витрины заодно с переездом в базу.
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $author = Admin::query()->first();

        foreach ($this->articles() as $row) {
            Article::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [...$row, 'admin_id' => $author?->id],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function articles(): array
    {
        return [
            [
                'slug' => 'lebanese-coffee',
                'title' => 'Ливанский кофе: ритуал гостеприимства',
                'tag' => 'Наследие',
                'excerpt' => 'Почему чашка кофе в Бейруте — это не напиток, а знак уважения. И как мы переносим этот ритуал в обжарку.',
                'body' => null,
                'cover_path' => 'img/dallah.webp',
                'status' => ArticleStatus::Published,
                'published_at' => Carbon::parse('2026-08-12 10:00'),
            ],
            [
                'slug' => 'how-to-brew-cezve',
                'title' => 'Как заваривать кофе в турке',
                'tag' => 'Приготовление',
                'excerpt' => 'Короткий гид: помол, вода, пенка. Для тех, кто хочет домашний ритуал без лишней мистики.',
                'body' => implode("\n\n", [
                    'Турка — не фольклор, а точный способ. Мелкий помол, холодная вода, медленный огонь. Пенка поднимается дважды: первый раз снимаем, второй — снимаем с огня.',
                    'Для ливанского ритуала берите смесь Intensive или молотую арабику Al Jar. Кардамон — по желанию, не обязательное правило дома.',
                    'Пропорция-ориентир: 7–8 г на 70 мл. Вода — фильтрованная. Не кипятите кофе в пузырях: горечь появится быстрее, чем аромат.',
                    'Свежесть важна и здесь: для турки зерно хорошо раскрывается на второй-третьей неделе после обжарки. Слишком молодое ещё газит и даёт пену вместо вкуса.',
                ]),
                'cover_path' => 'img/brew.webp',
                'status' => ArticleStatus::Published,
                'published_at' => Carbon::parse('2026-08-04 10:00'),
            ],
            [
                'slug' => 'roast-freshness',
                'title' => 'Почему дата обжарки важнее сорта',
                'tag' => 'Обжарка',
                'excerpt' => 'Свежесть — наш главный аргумент. Разбираем, что происходит с зерном на второй, десятый и тридцатый день.',
                'body' => null,
                'cover_path' => 'img/roaster.webp',
                'status' => ArticleStatus::Published,
                'published_at' => Carbon::parse('2026-07-28 10:00'),
            ],
        ];
    }
}
