<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Get the customer making the request.
     *
     * В приложении два гварда, поэтому $request->user() возвращает либо
     * покупателя, либо сотрудника. Контроллеры витрины работают только с
     * первым и говорят об этом прямо, а не надеются на маршрут.
     */
    protected function customer(Request $request): Customer
    {
        $customer = $request->user();

        if (! $customer instanceof Customer) {
            abort(403);
        }

        return $customer;
    }
}
