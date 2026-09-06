<?php

namespace App\Notifications;

use App\Models\Admin;
use App\Models\WholesaleLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Уведомление менеджера о новой оптовой заявке.
 *
 * В очередь: недоступный почтовый сервер не должен ронять отправку формы —
 * посетитель не обязан знать о наших проблемах с рассылкой.
 *
 * Каналы выбираются у получателя, а не задаются здесь: когда появится
 * бот, добавится один канал в этом методе, а не двадцать вызовов по коду.
 */
class WholesaleLeadReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WholesaleLead $lead) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof Admin && $notifiable->notify_leads ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->lead;

        return (new MailMessage)
            ->subject("Оптовая заявка {$lead->number} — {$lead->company}")
            ->greeting('Новая заявка от опта')
            ->line("Компания: {$lead->company} ({$lead->business_type->label()})")
            ->line("Контакт: {$lead->name}, {$lead->phone}".($lead->email ? ", {$lead->email}" : ''))
            ->line($lead->volume ? "Объём: {$lead->volume}" : 'Объём не указан.')
            ->line($lead->comment ? "Комментарий: {$lead->comment}" : 'Комментария нет.')
            ->action('Открыть в панели', url('/admin/leads'))
            ->salutation('Al Jar Coffee');
    }
}
