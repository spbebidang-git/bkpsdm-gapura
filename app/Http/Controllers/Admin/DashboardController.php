<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

class DashboardController
{
    public function __invoke(Request $request)
    {
        return view('admin.dashboard.index');
    }
}
