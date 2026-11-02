<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\EmployeeStatusStoreRequest;
use App\Http\Requests\Admin\EmployeeStatusUpdateRequest;
use App\Models\Identity\EmployeeStatus;
use App\Services\Identity\EmployeeStatusService;
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

    public function store(EmployeeStatusStoreRequest $request)
    {
        $validated = $request->validated();

        $this->statuses->createStatus($validated);

        return to_route('admin.employee-status.index')->with('success', 'Status pegawai berhasil ditambahkan.');
    }

    public function edit(EmployeeStatus $employeeStatus)
    {
        return Inertia::render('Admin/EmployeeStatus/Form', ['employeeStatus' => $employeeStatus]);
    }

    public function update(EmployeeStatusUpdateRequest $request, EmployeeStatus $employeeStatus)
    {
        $validated = $request->validated();

        $this->statuses->updateStatus($employeeStatus, $validated);

        return to_route('admin.employee-status.index')->with('success', 'Status pegawai berhasil diperbarui.');
    }

    public function destroy(EmployeeStatus $employeeStatus)
    {
        $this->statuses->deleteStatus($employeeStatus);

        return to_route('admin.employee-status.index')->with('success', 'Status pegawai berhasil dihapus.');
    }
}
