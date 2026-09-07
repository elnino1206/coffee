<?php

namespace App\Enums;

/**
 * Состояние платежа.
 *
 * Свой короткий список вместо кодов провайдера: у Т-Банка их больше
 * двадцати, разница между `AUTHORIZING` и `3DS_CHECKING` магазину не
 * нужна, а привязка к чужому словарю означала бы переписывать интерфейс
 * при смене эквайринга. Исходный код провайдера остаётся в `payload`.
 */
enum PaymentStatus: string
{
    /** Заведён, покупатель ещё не открывал форму. */
    case New = 'new';

    /** Форма открыта, деньги не списаны: ждём результата. */
    case Pending = 'pending';

    /** Деньги списаны. Только это состояние считается оплатой. */
    case Succeeded = 'succeeded';

    /** Банк отказал или истёк срок оплаты. */
    case Failed = 'failed';

    /** Отменён до списания. */
    case Canceled = 'canceled';

    /** Деньги вернули покупателю — полностью или частично. */
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Создан',
            self::Pending => 'Ожидает оплаты',
            self::Succeeded => 'Оплачен',
            self::Failed => 'Отклонён',
            self::Canceled => 'Отменён',
            self::Refunded => 'Возвращён',
        };
    }

    /**
     * Дальше состояние само не изменится.
     *
     * Нужно опросу состояния платежей: спрашивать провайдера про
     * законченный платёж незачем.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::New, self::Pending => false,
            default => true,
        };
    }

    /**
     * Код состояния Т-Банка в наше состояние.
     *
     * `null` — код неизвестен: состояние не трогаем, ответ провайдера
     * всё равно сохраняется целиком. Молча приравнивать незнакомый код к
     * отказу нельзя: под ним может оказаться удачное списание.
     *
     * Двухстадийная оплата (холд, затем подтверждение) магазину не
     * нужна, поэтому `AUTHORIZED` считается ожиданием, а не оплатой:
     * оплата — только `CONFIRMED`.
     */
    public static function fromProvider(string $code): ?self
    {
        return match (strtoupper($code)) {
            'NEW' => self::New,
            'FORM_SHOWED', 'AUTHORIZING', '3DS_CHECKING', '3DS_CHECKED',
            'AUTHORIZED', 'CONFIRMING' => self::Pending,
            'CONFIRMED' => self::Succeeded,
            'REJECTED', 'DEADLINE_EXPIRED', 'ATTEMPTS_EXPIRED' => self::Failed,
            'CANCELED', 'REVERSING', 'REVERSED' => self::Canceled,
            'REFUNDING', 'REFUNDED', 'PARTIAL_REFUNDED' => self::Refunded,
            default => null,
        };
    }
}
