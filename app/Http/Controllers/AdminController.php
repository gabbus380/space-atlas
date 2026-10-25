<?php

namespace App\Http\Controllers;

use App\Support\Kpi;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function show(): View
    {
        return view('admin.show', ['kpi' => Kpi::snapshot()]);
    }
}
