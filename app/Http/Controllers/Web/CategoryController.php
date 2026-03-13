<?php

namespace App\Http\Controllers\Web;

use App\Models\Course\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController
{
    public function index()
    {
        return Inertia::render('Category/Index');
    }

    public function show(Category $category)
    {
        return Inertia::render('Category/Show', [
            'courses' => $category->courses()
                ->where('status', 'publish')
                ->where('is_locked', true)
                ->get()
        ]);
    }
}
