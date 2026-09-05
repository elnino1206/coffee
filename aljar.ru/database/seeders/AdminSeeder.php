<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Учётная запись сотрудника для входа в админку.
     *
     * Пароль известный, поэтому на боевом окружении сидер молча ничего не
     * делает: регистрации в админке нет, и такая запись стала бы открытой
     * дверью. Боевого администратора заводят вручную.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('AdminSeeder пропущен: на production демонстрационный администратор не создаётся.');

            return;
        }

        Admin::query()->updateOrCreate(
            ['email' => 'admin@aljar.ru'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('password'),
                'is_active' => true,
                'notify_orders' => true,
                'notify_leads' => true,
            ],
        );
    }
}
