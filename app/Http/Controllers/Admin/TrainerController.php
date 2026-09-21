<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course\Trainer;
use App\Services\Course\TrainerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class TrainerController extends Controller
{
    public function __construct(protected TrainerService $trainers) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->trainers->getForAdmin())
                ->addIndexColumn()
                ->addColumn('photo_url', fn ($row) => $row->photo_src)
                ->make();
        }

        return Inertia::render('Admin/Trainer/Index');
    }

    public function create()
    {
        return Inertia::render('Admin/Trainer/Form', ['trainer' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validateTrainer($request);

        $this->trainers->create($data);

        return to_route('admin.trainer.index')->with('success', 'Trainer berhasil ditambahkan.');
    }

    public function edit(Trainer $trainer)
    {
        return Inertia::render('Admin/Trainer/Form', [
            'trainer' => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'jabatan' => $trainer->jabatan,
                'photo' => $trainer->photo,
                'photo_src' => $trainer->photo_src,
            ],
        ]);
    }

    public function update(Request $request, Trainer $trainer)
    {
        $data = $this->validateTrainer($request);

        $this->trainers->update($trainer, $data);

        return to_route('admin.trainer.index')->with('success', 'Trainer berhasil diperbarui.');
    }

    public function destroy(Trainer $trainer)
    {
        $this->trainers->delete($trainer->id);

        return to_route('admin.trainer.index')->with('success', 'Trainer berhasil dihapus.');
    }

    protected function validateTrainer(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048|mimes:png,jpg,jpeg',
        ]);
    }
}
