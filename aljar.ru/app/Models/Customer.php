<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $rebill_id
 * @property string|null $card_mask
 * @property Carbon|null $card_bound_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
/*
 * Привязка карты (`rebill_id`, `card_mask`, `card_bound_at`) намеренно не
 * в списке заполняемых: её ставит платёжный контур по ответу банка, и
 * прийти она может только оттуда — не из формы профиля. Идентификатор
 * списания скрыт: по нему снимают деньги, и на клиенте ему делать
 * нечего.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'rebill_id'])]
class Customer extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'card_bound_at' => 'datetime',
        ];
    }

    /**
     * Есть ли карта, с которой можно списать без участия покупателя.
     *
     * Нужно подписке: без привязки регулярное списание невозможно, и
     * оформлять её незачем.
     */
    public function hasBoundCard(): bool
    {
        return $this->rebill_id !== null;
    }
}
