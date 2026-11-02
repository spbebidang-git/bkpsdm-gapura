<?php

namespace App\Http\Controllers\Admin;

use App\Services\Reporting\AdminCertificateService;

class BulkCertificateController
{
    public function __invoke($courseId, AdminCertificateService $certificates)
    {
        $certificates->queueBulk($courseId);

        return back()->with('success', 'Pengiriman sertifikat dijadwalkan.');
    }
}
