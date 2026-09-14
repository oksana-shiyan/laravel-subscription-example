<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class BillingBlockedController extends Controller
{
    public function __invoke(): View
    {
        return view('billing.blocked');
    }
}
