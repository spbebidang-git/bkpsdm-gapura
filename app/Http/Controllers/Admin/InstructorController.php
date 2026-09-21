<?php

namespace App\Http\Controllers\Admin;

use App\Models\Identity\User;
use Illuminate\Http\Request;

/**
 * Instructor admin UI deferred — module will be absorbed into admin later.
 */
class InstructorController
{
    public function index()
    {
        return redirect()->route('admin.dashboard')
            ->with('danger', 'Modul instruktur dinonaktifkan.');
    }

    public function create()
    {
        return $this->index();
    }

    public function store(Request $request)
    {
        return $this->index();
    }

    public function edit(User $instructor)
    {
        return $this->index();
    }

    public function update(Request $request, User $instructor)
    {
        return $this->index();
    }

    public function destroy(User $instructor)
    {
        return $this->index();
    }
}
