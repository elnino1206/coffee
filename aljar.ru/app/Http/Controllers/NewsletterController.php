<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Подписка на рассылку.
 *
 * Адрес не удаляется и не заводится повторно: запись одна на почту, а
 * отписка и возврат — это отметки времени на ней. Иначе повторная
 * подписка теряла бы историю, а уникальный индекс мешал бы вернуться.
 */
class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'source' => ['nullable', 'string', 'max:40'],
        ]);

        $email = mb_strtolower(trim($data['email']));

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $email]);

        $wasSubscribed = $subscriber->exists && $subscriber->isSubscribed();

        $subscriber->fill([
            'source' => $data['source'] ?? $subscriber->source,
            'unsubscribed_at' => null,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $wasSubscribed
                ? 'Вы уже подписаны — будем писать.'
                : 'Подписали. Письма приходят редко и по делу.',
        ]);

        return back();
    }
}
