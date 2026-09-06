<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Enums\LeadStatus;
use App\Models\NumberSequence;
use App\Models\WholesaleLead;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Демонстрационные оптовые заявки для админки.
     *
     * Тексты взяты из макета versions/v2-anim/admin/js/demo.js: раздел
     * должен выглядеть как в утверждённом прототипе, а пустая таблица
     * ничего не проверяет.
     *
     * На боевом окружении сидер не работает: выдуманные заявки попали бы
     * в работу менеджера и съели бы номера из последовательности.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('LeadSeeder пропущен: на production демонстрационные заявки не создаются.');

            return;
        }

        $leads = [
            [
                'name' => 'Сергей Ватагин',
                'company' => 'Кофейня «Полдень»',
                'phone' => '+7 903 771-20-40',
                'email' => 's.vatagin@polden.ru',
                'business_type' => BusinessType::Cafe,
                'volume' => '20 кг/мес, эспрессо-смесь',
                'comment' => 'Нужен стабильный профиль под рожок, дегустация до контракта.',
                'status' => LeadStatus::New,
                'days' => 0,
            ],
            [
                'name' => 'Анна Терехова',
                'company' => 'ООО «Северград»',
                'phone' => '+7 921 604-13-77',
                'email' => 'a.terekhova@severgrad.ru',
                'business_type' => BusinessType::Office,
                'volume' => '8 кг/мес',
                'comment' => 'Офис на 60 человек, нужна аренда кофемашины.',
                'status' => LeadStatus::New,
                'days' => 1,
            ],
            [
                'name' => 'Рустам Ахметов',
                'company' => 'Отель «Гранат»',
                'phone' => '+7 917 245-90-12',
                'email' => 'r.ahmetov@granat-hotel.ru',
                'business_type' => BusinessType::Hotel,
                'volume' => '35 кг/мес',
                'comment' => 'Завтраки, нужен фильтр и турка. Просят прайс от объёма.',
                'status' => LeadStatus::Work,
                'manager_comment' => 'Выслали прайс от 30 кг, ждём ответ.',
                'days' => 3,
            ],
            [
                'name' => 'Ирина Голубева',
                'company' => 'Ресторан «Дым»',
                'phone' => '+7 962 118-44-05',
                'email' => 'irina@dym.rest',
                'business_type' => BusinessType::Restaurant,
                'volume' => '12 кг/мес',
                'comment' => 'Договор подписан, первая поставка ушла.',
                'status' => LeadStatus::Done,
                'manager_comment' => 'Договор подписан 4 числа, поставка раз в две недели.',
                'days' => 6,
            ],
        ];

        foreach ($leads as $lead) {
            $received = now()->subDays($lead['days']);

            WholesaleLead::query()->create([
                ...collect($lead)->except('days')->all(),
                'number' => NumberSequence::next('lead'),
                'consent_at' => $received,
                'created_at' => $received,
                'updated_at' => $received,
            ]);
        }
    }
}
