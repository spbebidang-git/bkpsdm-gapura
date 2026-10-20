<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class CertificatesExport extends StringValueBinder implements FromQuery, WithCustomValueBinder, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Nama peserta', 'Kelas', 'Tanggal selesai', 'Ketersediaan sertifikat', 'Status pengiriman SSO', 'URL sertifikat'];
    }

    public function map($row): array
    {
        $available = filled($row->url_certificate) && $row->url_certificate !== '-';

        return [
            $row->participant?->name ?? '-',
            $row->course?->title ?? '-',
            $row->finished_at?->format('d/m/Y') ?? '-',
            $available ? 'Tersedia' : 'Tidak tersedia',
            $row->is_post_certificate ? 'Dikirim' : 'Menunggu',
            $available ? $row->url_certificate : '',
        ];
    }
}
