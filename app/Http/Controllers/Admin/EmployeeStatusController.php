<?php

namespace App\Http\Controllers\Admin;

use App\Models\Identity\EmployeeStatus;
use App\Services\Identity\EmployeeStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class EmployeeStatusController
{
    public function __construct(
        private readonly EmployeeStatusService $statuses,
    ) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->statuses->getForTable())
                ->addIndexColumn()
                ->make();
        }
        return Inertia::render('Admin/EmployeeStatus/Index');
    }

    public function create()
    {
        return Inertia::render('Admin/EmployeeStatus/Form', ['employeeStatus' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'unique:employee_status,title'],
        ], [], ['title' => 'Nama status pegawai']);

        $this->statuses->createStatus($validated);

        return to_route('admin.employee-status.index')->with('success', 'Status pegawai berhasil ditambahkan.');
    }

    public function edit(EmployeeStatus $employeeStatus)
    {
        return Inertia::render('Admin/EmployeeStatus/Form', ['employeeStatus' => $employeeStatus]);
    }

    public function update(Request $request, EmployeeStatus $employeeStatus)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('employee_status', 'title')->ignore($employeeStatus->id)],
        ], [], ['title' => 'Nama status pegawai']);

        $this->statuses->updateStatus($employeeStatus, $validated);

        return to_route('admin.employee-status.index')->with('success', 'Status pegawai berhasil diperbarui.');
    }

    public function destroy(EmployeeStatus $employeeStatus)
    {
        $this->statuses->deleteStatus($employeeStatus);

        return to_route('admin.employee-status.index')->with('success', 'Status pegawai berhasil dihapus.');
    }
}
