<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Support\HelpService;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function __construct(protected HelpService $helps) {}

    public function index()
    {
        return Inertia::render('Help/Index', [
            'helps' => $this->helps->getForPortal(),
        ]);
    }
}
