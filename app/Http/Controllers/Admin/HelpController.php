<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Help;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HelpController extends Controller
{
    // tampilkan index (blade)
    public function index()
    {
        if (request()->ajax()) {
            $query = Help::orderBy('id', 'desc')->get();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<a class="btn btn-sm btn-warning" href="' . route('admin.help.edit', $row->id) . '">';
                    $btn .= '<i class="bx bx-edit"></i></a>';
                    $btn .= '<form action="' . route('admin.help.destroy', $row->id) . '" method="POST" class="form-delete">';
                    $btn .= csrf_field();
                    $btn .= method_field('DELETE');
                    $btn .= '<button class="btn btn-sm btn-danger"><i class="bx bx-trash"></i></button>';
                    $btn .= '</form></div>';
                    return $btn;
                })
                ->addColumn('is_active', function ($row) {
                    return $row->is_active ? 'Active' : 'Inactive';
                })
                ->rawColumns(['action'])
                ->make();
        }

        return view('admin.help.index');
    }

    // form create
    public function create()
    {
        return view('admin.help.create');
    }

    // store
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        Help::create([
            'question' => $request->question,
            'answer' => sanitize_html($request->answer),
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        return to_route('admin.help.index')->with('success', 'Help entry has been created.');
    }

    // edit form
    public function edit(Help $help)
    {
        return view('admin.help.edit', compact('help'));
    }

    // update
    public function update(Request $request, Help $help)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $help->update([
            'question' => $request->question,
            'answer' => sanitize_html($request->answer),
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : false,
        ]);

        return to_route('admin.help.index')->with('success', 'Help entry has been updated.');
    }

    // destroy
    public function destroy(Help $help)
    {
        $help->delete();

        return to_route('admin.help.index')->with('success', 'Help entry has been deleted.');
    }
}
