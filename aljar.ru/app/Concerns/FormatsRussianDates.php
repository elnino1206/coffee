<?php

namespace App\Concerns;

use Carbon\CarbonInterface;

/**
 * Дата словами по-русски: «6 сентября 2026».
 *
 * Месяцы заданы списком, а не берутся из локали: приложение живёт в
 * английской локали (русских языковых файлов у Laravel нет, и их
 * включение сломало бы сообщения проверок), а покупателю нужна русская
 * дата.
 */
trait FormatsRussianDates
{
    protected function russianDate(CarbonInterface $date): string
    {
        $months = [
            'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
            'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
        ];

        return $date->day.' '.$months[$date->month - 1].' '.$date->year;
    }
}
