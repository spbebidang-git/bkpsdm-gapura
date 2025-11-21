<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Help;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function index()
    {
        $helps = Help::where('is_active', true)
            ->orderBy('id', 'desc')
            ->get(['id', 'question', 'answer']);

        return Inertia::render('Help/Index', [
            'helps' => $helps,
        ]);
    }
}
