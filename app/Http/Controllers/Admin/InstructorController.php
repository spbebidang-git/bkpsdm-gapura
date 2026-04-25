<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Helper\UploadMedia;
use Illuminate\Http\Request;
use App\Services\User\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class InstructorController
{
    protected $user;

    public function __construct(UserService $userService)
    {
        $this->user = $userService;
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->user->getInstructor())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn  = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<a class="btn btn-sm btn-warning" href="' . route('admin.instructor.edit', $row->id) . '">';
                    $btn .= '<i class="bx bx-edit"></i></a>';
                    $btn .= '<form action="' . route('admin.instructor.destroy', $row->id) . '" method="POST" class="form-delete">';
                    $btn .= csrf_field();
                    $btn .= method_field('DELETE');
                    $btn .= '<button class="btn btn-sm btn-danger"><i class="bx bx-trash"></i></button>';
                    $btn .= '</form></div>';
                    return $btn;
                })
                ->addColumn('image', function ($row) {
                    return "<img style='width:64px;' class='border rounded' src='" . asset($row->profile_picture) . "'/>";
                })
                ->rawColumns(['action', 'image'])
                ->make();
        }

        return view('admin.instructor.index');
    }

    public function create()
    {
        return view('admin.instructor.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|string|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
            ],
            'image'    => 'nullable|image|mimes:png,jpg|max:1024',
        ], [
            'password.regex' => 'Password harus menggunakan kombinasi huruf dan angka.',
        ]);


        try {
            DB::beginTransaction();

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            if ($request->image) {
                $user->image = UploadMedia::file($request->image, User::$directoryImage);
                $user->save();
            }

            $user->assignRole('Instructor');

            DB::commit();

            return to_route('admin.instructor.index')
                ->with('success', 'Instruktur berhasil disimpan.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.instructor.index')
                ->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function edit(User $instructor)
    {
        return view('admin.instructor.edit', compact('instructor'));
    }

    public function update(Request $request, User $instructor)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|string|email|unique:users,email,' . $instructor->id,
            'password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
            ],
            'image'    => 'nullable|image|mimes:png,jpg|max:512',
        ], [
            'password.regex' => 'Password harus menggunakan kombinasi huruf dan angka.',
        ]);

        try {
            DB::beginTransaction();

            $instructor->update([
                'name'  => $request->name,
                'email' => $request->email,
            ]);

            if ($request->password) {
                $instructor->password = Hash::make($request->password);
                $instructor->save();
            }

            if ($request->image) {
                $instructor->image = UploadMedia::file($request->image, User::$directoryImage);
                $instructor->save();
            }

            DB::commit();

            return to_route('admin.instructor.index')
                ->with('success', 'Instruktur berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.instructor.index')
                ->with('danger', 'Terjadi kesalahan saat memperbarui data.');
        }
    }

    public function destroy(User $instructor)
    {
        try {
            DB::beginTransaction();

            $instructor->delete();

            DB::commit();

            return to_route('admin.instructor.index')
                ->with('success', 'Instruktur berhasil dihapus.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.instructor.index')
                ->with('danger', 'Terjadi kesalahan saat menghapus data.');
        }
    }
}
