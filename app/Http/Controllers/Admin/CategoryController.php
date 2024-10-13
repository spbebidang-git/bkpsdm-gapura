<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CategoryController
{
    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(Category::all())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<a class="btn btn-sm btn-warning" href=' . route('admin.category.edit', $row->id) . '>';
                    $btn .= '<i class="bx bx-edit"></i></a>';
                    $btn .= '<form action="' . route('admin.category.destroy', $row->id) . '" method="POST" onsubmit="return confirmDelete(event)">';
                    $btn .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                    $btn .= '<input type="hidden" name="_method" value="DELETE">';
                    $btn .= '<button class="btn btn-sm btn-danger">';
                    $btn .= '<i class="bx bx-trash"></i></button>';
                    $btn .= '</form>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make();
        }

        return view('admin.category.index');
    }

    public function create()
    {
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'is_primary' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            Category::create([
                'title' => $request->title,
                'slug' => str()->slug($request->title),
                'is_primary' => $request->is_primary,
            ]);

            DB::commit();

            return to_route('admin.category.index')->with('success', 'Category has been saved.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.category.index')->with('danger', 'Something went wrong.');
        }
    }

    public function edit(Category $category)
    {
        return view('admin.category.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'title' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $category->update($request->all());

            DB::commit();

            return to_route('admin.category.index')->with('success', 'Category has been updated.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.category.index')->with('danger', 'Something went wrong.');
        }
    }

    public function destroy(Category $category)
    {
        try {
            DB::beginTransaction();

            $category->delete();

            DB::commit();

            return to_route('admin.category.index')->with('success', 'Category has been deleted.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.category.index')->with('danger', 'Something went wrong.');
        }
    }
}
