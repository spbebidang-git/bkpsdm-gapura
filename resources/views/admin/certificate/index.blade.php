@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Certificate Lists
        </h5>
        <div class="card-body p-0 overflow-hidden">
            <div class="table-responsive text-wrap overflow-hidden p-4">
                <table class="table table-bordered text-center" id="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th class="text-center">Nama Kegiatan</th>
                            <th class="text-center">Penyelenggara</th>
                            <th class="text-center">Tempat Kegiatan</th>
                            <th class="text-center">Tanggal</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">NIP</th>
                            <th class="text-center">Url Sertifikat</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0"></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ url()->current() }}',
                stripeClasses: [],
                "columnDefs": [{
                    "className": "dt-center",
                    "targets": "_all"
                }],
                "bPaginate": true,
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        width: '10px',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama_kegiatan',
                        name: 'nama_kegiatan',
                    },
                    {
                        data: 'penyelenggara',
                        name: 'penyelenggara',
                    },
                    {
                        data: 'tempat_kegiatan',
                        name: 'tempat_kegiatan',
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal',
                    },
                    {
                        data: 'status',
                        name: 'status',
                    },
                    {
                        data: 'nip',
                        name: 'nip',
                    },
                    {
                        data: 'sertifikat',
                        name: 'sertifikat',
                    },
                ],
                language: {
                    paginate: {
                        next: '&#8594;',
                        previous: '&#8592;'
                    }
                }
            });
        });
    </script>
@endpush
