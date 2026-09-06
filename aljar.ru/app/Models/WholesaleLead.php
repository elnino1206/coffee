<?php

namespace App\Models;

use App\Enums\BusinessType;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Оптовая заявка.
 *
 * Отдельная сущность от обращения с «Контактов»: другой набор полей и
 * другой адресат — отдел продаж B2B, а не поддержка розницы.
 *
 * @property int $id
 * @property string $number
 * @property string $name
 * @property string $company
 * @property string $phone
 * @property string|null $email
 * @property BusinessType $business_type
 * @property string|null $volume
 * @property string|null $comment
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property LeadStatus $status
 * @property string|null $manager_comment
 * @property Carbon $consent_at
 * @property Carbon|null $created_at
 */
#[Fillable([
    'number', 'name', 'company', 'phone', 'email', 'business_type', 'volume',
    'comment', 'attachment_path', 'attachment_name', 'status', 'manager_comment', 'consent_at',
])]
class WholesaleLead extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
            'status' => LeadStatus::class,
            'consent_at' => 'datetime',
        ];
    }

    /**
     * Дата заявки для админки: «6 сентября 2026».
     */
    public function receivedAtLabel(): string
    {
        $months = [
            'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
            'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
        ];

        $date = $this->created_at ?? now();

        return $date->day.' '.$months[$date->month - 1].' '.$date->year;
    }
}
