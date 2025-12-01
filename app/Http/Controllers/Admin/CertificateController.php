<?php

namespace App\Http\Controllers\Admin;

use App\Models\Learn\Learn;
use Yajra\DataTables\Facades\DataTables;

class CertificateController
{
    public function index()
    {
        if (request()->ajax()) {
            $response = Learn::with(['course', 'participant'])
                ->whereNotNull('url_certificate')
                ->get()
                ->map(function ($row) {
                    return [
                        'name' => $row->participant->name,
                        'course' => $row->course->title,
                        'url_certificate' => '<a href="' . $row->url_certificate . '" target="_blank" class="btn btn-sm btn-primary">Download Certificate</a>',
                    ];
                });

            return DataTables::of($response)
                ->addIndexColumn()
                ->rawColumns(['url_certificate'])
                ->make(true);
        }

        return view('admin.certificate.index');
    }
}
