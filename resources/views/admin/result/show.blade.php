@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Results
            <a class="btn btn-outline-secondary" href="{{ route('admin.result.index') }}">Back</a>
        </h5>
        <div class="row px-4 py-4 border-bottom">
            <div class="col-md-3">
                <select id="filter-nama" class="form-select">
                    <option value="">-- Semua Nama --</option>
                    @foreach ($names as $name)
                        <option value="{{ $name->id }}">{{ $name->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <select id="filter-kegiatan" class="form-select">
                    <option value="">-- Semua Kegiatan --</option>
                    @foreach ($activities as $kegiatan)
                        <option value="{{ $kegiatan->id }}">{{ $kegiatan->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card-body p-0 overflow-hidden">
            <div class="table-responsive text-wrap overflow-hidden p-4">
                <table class="table table-bordered text-center" id="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th class="text-center">nip</th>
                            <th class="text-center">nama</th>
                            <th class="text-center">jabatan</th>
                            <th class="text-center">kegiatan</th>
                            <th class="text-center">nilai</th>
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
            var table = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ url()->current() }}',
                    data: function(d) {
                        d.nama = $('#filter-nama').val();
                        d.kegiatan = $('#filter-kegiatan').val();
                    }
                },
                stripeClasses: [],
                columnDefs: [{
                    className: "dt-center",
                    targets: "_all"
                }],
                bPaginate: true,
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        width: '10px',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nip',
                        name: 'nip'
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'jabatan',
                        name: 'jabatan'
                    },
                    {
                        data: 'kegiatan',
                        name: 'kegiatan'
                    },
                    {
                        data: 'nilai',
                        name: 'nilai'
                    },
                ],
                language: {
                    paginate: {
                        next: '&#8594;',
                        previous: '&#8592;'
                    }
                }
            });

            $('#filter-nama, #filter-kegiatan').on('change', function() {
                table.draw();
            });
        });
    </script>
@endpush
