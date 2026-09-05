<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Журнал.
 *
 * Статьи пока лежат здесь списком, а не в базе: таблица `articles`
 * появится вместе с админкой, где их будут заводить. Форма записи
 * повторяет будущие колонки, поэтому переезд сведётся к замене этого
 * метода запросом.
 *
 * Тексты двух статей ещё не написаны — в прототипе их тоже не было,
 * там страница статьи показывала один и тот же материал. До получения
 * текстов от заказчика в этих статьях стоит пометка.
 */
class BlogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('info/Blog', [
            'articles' => $this->articles()
                ->map(fn (array $article) => collect($article)->except('body')->all())
                ->values(),
        ]);
    }

    public function show(string $slug): Response
    {
        $article = $this->articles()->firstWhere('slug', $slug);

        abort_if($article === null, 404);

        return Inertia::render('info/Article', [
            'article' => $article,
            'more' => $this->articles()
                ->reject(fn (array $item) => $item['slug'] === $slug)
                ->map(fn (array $item) => collect($item)->except('body')->all())
                ->values(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function articles(): Collection
    {
        return collect([
            [
                'slug' => 'lebanese-coffee',
                'title' => 'Ливанский кофе: ритуал гостеприимства',
                'tag' => 'Наследие',
                'date' => '12 августа 2026',
                'image' => 'img/dallah.webp',
                'excerpt' => 'Почему чашка кофе в Бейруте — это не напиток, а знак уважения. И как мы переносим этот ритуал в обжарку.',
                'body' => [],
            ],
            [
                'slug' => 'how-to-brew-cezve',
                'title' => 'Как заваривать кофе в турке',
                'tag' => 'Приготовление',
                'date' => '4 августа 2026',
                'image' => 'img/brew.webp',
                'excerpt' => 'Короткий гид: помол, вода, пенка. Для тех, кто хочет домашний ритуал без лишней мистики.',
                'body' => [
                    'Турка — не фольклор, а точный способ. Мелкий помол, холодная вода, медленный огонь. Пенка поднимается дважды: первый раз снимаем, второй — снимаем с огня.',
                    'Для ливанского ритуала берите смесь Intensive или молотую арабику Al Jar. Кардамон — по желанию, не обязательное правило дома.',
                    'Пропорция-ориентир: 7–8 г на 70 мл. Вода — фильтрованная. Не кипятите кофе в пузырях: горечь появится быстрее, чем аромат.',
                    'Свежесть важна и здесь: для турки зерно хорошо раскрывается на второй-третьей неделе после обжарки. Слишком молодое ещё газит и даёт пену вместо вкуса.',
                ],
            ],
            [
                'slug' => 'roast-freshness',
                'title' => 'Почему дата обжарки важнее сорта',
                'tag' => 'Обжарка',
                'date' => '28 июля 2026',
                'image' => 'img/roaster.webp',
                'excerpt' => 'Свежесть — наш главный аргумент. Разбираем, что происходит с зерном на второй, десятый и тридцатый день.',
                'body' => [],
            ],
        ]);
    }
}
