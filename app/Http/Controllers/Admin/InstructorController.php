<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Helper\UploadMedia;
use Illuminate\Http\Request;
use GuzzleHttp\Promise\Create;
use App\Services\User\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class InstructorController
{
    protected $user;

    public function __construct(UserService $userService)
    {
        $this->user =  $userService;
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->user->getInstructor())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<a class="btn btn-sm btn-warning" href=' . route('admin.instructor.edit', $row->id) . '>';
                    $btn .= '<i class="bx bx-edit"></i></a>';
                    $btn .= '<form action="' . route('admin.instructor.destroy', $row->id) . '" method="POST" onsubmit="return confirmDelete(event)">';
                    $btn .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                    $btn .= '<input type="hidden" name="_method" value="DELETE">';
                    $btn .= '<button class="btn btn-sm btn-danger">';
                    $btn .= '<i class="bx bx-trash"></i></button>';
                    $btn .= '</form>';
                    $btn .= '</div>';
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
            'name' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
            'image' => 'nullable|image|mimes:png,jpg|max:512',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            if ($request->image) {
                $user->image = UploadMedia::file($request->image, User::$directoryImage);
                $user->save();
            }

            $user->assignRole('Instructor');

            DB::commit();

            return to_route('admin.instructor.index')->with('success', 'Instructor has been saved.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.instructor.index')->with('danger', 'Something went wrong.');
        }
    }

    public function edit(User $instructor)
    {
        return view('admin.instructor.edit', compact('instructor'));
    }

    public function update(Request $request, User $instructor)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'nullable|string|min:8',
            'image' => 'nullable|image|mimes:png,jpg|max:512',
        ]);

        try {
            DB::beginTransaction();

            $instructor->update([
                'name' => $request->name,
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

            return to_route('admin.instructor.index')->with('success', 'Instructor has been updated.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.instructor.index')->with('danger', 'Something went wrong.');
        }
    }

    public function destroy(User $instructor)
    {
        try {
            DB::beginTransaction();

            $instructor->delete();

            DB::commit();

            return to_route('admin.instructor.index')->with('success', 'Instructor has been deleted.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.instructor.index')->with('danger', 'Something went wrong.');
        }
    }
}
