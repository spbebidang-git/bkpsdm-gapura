<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HelpStoreRequest;
use App\Http\Requests\Admin\HelpUpdateRequest;
use App\Models\Support\Help;
use App\Services\Support\HelpService;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class HelpController extends Controller
{
    public function __construct(protected HelpService $helps) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->helps->getForAdmin())
                ->addIndexColumn()
                ->addColumn('is_active_label', fn ($row) => $row->is_active ? 'Active' : 'Inactive')
                ->make();
        }

        return Inertia::render('Admin/Help/Index');
    }

    public function create()
    {
        return Inertia::render('Admin/Help/Form', ['help' => null]);
    }

    public function store(HelpStoreRequest $request)
    {
        $this->helps->create([
            'question' => $request->question,
            'answer' => $request->answer,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('admin.help.index')->with('success', 'Help entry has been created.');
    }

    public function edit(Help $help)
    {
        return Inertia::render('Admin/Help/Form', [
            'help' => [
                'id' => $help->id,
                'question' => $help->question,
                'answer' => $help->answer,
                'is_active' => (bool) $help->is_active,
            ],
        ]);
    }

    public function update(HelpUpdateRequest $request, Help $help)
    {
        $this->helps->update($help, [
            'question' => $request->question,
            'answer' => $request->answer,
            'is_active' => $request->boolean('is_active'),
        ]);

        return to_route('admin.help.index')->with('success', 'Help entry has been updated.');
    }

    public function destroy(Help $help)
    {
        $this->helps->delete($help->id);

        return to_route('admin.help.index')->with('success', 'Help entry has been deleted.');
    }
}
