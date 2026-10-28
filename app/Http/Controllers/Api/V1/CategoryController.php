<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CategoryResource;
use App\Http\Resources\V1\CourseResource;
use App\Models\Course\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Daftar semua kategori dengan jumlah course
     */
    public function index()
    {
        $categories = Category::withCount(['courses' => function ($query) {
            $query->where('status', 'publish');
        }])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data kategori berhasil diambil.',
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Daftar course per kategori
     */
    public function courses(string $id, Request $request)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        $courses = $category->courses()
            ->with(['employeeStatus', 'opds'])
            ->where('status', 'publish')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return CourseResource::collection($courses)->additional([
            'success' => true,
            'message' => 'Data pelatihan per kategori berhasil diambil.',
            'category' => new CategoryResource($category),
        ]);
    }
}
