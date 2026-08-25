<?php

namespace App\Providers;

use App\Models\Passkey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Passkeys::usePasskeyModel(Passkey::class);

        $this->teachSqliteToLowercaseCyrillic();
    }

    /**
     * Поиск по каталогу сравнивает строки в нижнем регистре. Встроенная
     * функция lower() в SQLite умеет только латиницу, поэтому «жасмин» не
     * находил «Жасмин» — в MySQL и PostgreSQL такого расхождения нет.
     * Своя функция уравнивает разработку с боевой базой, а не подгоняет
     * тесты под особенность драйвера.
     */
    protected function teachSqliteToLowercaseCyrillic(): void
    {
        Event::listen(function (ConnectionEstablished $event): void {
            if ($event->connection->getDriverName() !== 'sqlite') {
                return;
            }

            $pdo = $event->connection->getPdo();

            if (method_exists($pdo, 'createFunction')) {
                $pdo->createFunction('lower', fn (?string $value) => $value === null ? null : mb_strtolower($value), 1);
            } elseif (method_exists($pdo, 'sqliteCreateFunction')) {
                $pdo->sqliteCreateFunction('lower', fn (?string $value) => $value === null ? null : mb_strtolower($value), 1);
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
