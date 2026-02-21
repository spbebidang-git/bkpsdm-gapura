@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Daftar Sertifikat</span>
            <div class="d-flex gap-2">
                <button class="btn btn-warning btn-sm" id="btnSyncPush">
                    <i class="ti ti-upload me-1"></i>Sync Push
                </button>
                <button class="btn btn-info btn-sm text-white" id="btnSyncPull">
                    <i class="ti ti-download me-1"></i>Sync Pull
                </button>
            </div>
        </h5>

        {{-- Status Legend --}}
        <div class="px-4 pt-3 pb-1 d-flex flex-wrap gap-2 align-items-center">
            <small class="text-muted fw-semibold me-1">Status:</small>
            <span class="badge bg-secondary">Belum Selesai</span>
            <span class="badge bg-warning text-dark">Menunggu Sync</span>
            <span class="badge bg-info text-dark">Dikirim ke SSO</span>
            <span class="badge bg-success">Sertifikat Tersedia</span>
        </div>

        <div class="card-body p-0 overflow-hidden">
            <div class="table-responsive text-wrap overflow-hidden p-4">
                <table class="table table-bordered text-center" id="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">NIP</th>
                            <th class="text-center">Unit Kerja</th>
                            <th class="text-center">Nama Kegiatan</th>
                            <th class="text-center">Tanggal Selesai</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
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
            const table = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ url()->current() }}',
                stripeClasses: [],
                columnDefs: [{
                    className: "dt-center",
                    targets: "_all"
                }],
                bPaginate: true,
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        width: '10px',
                        orderable: false,
                        searchable: false
                    },
                    { data: 'name', name: 'name' },
                    { data: 'nip', name: 'nip' },
                    { data: 'satker', name: 'satker' },
                    { data: 'course', name: 'course' },
                    { data: 'finished_at', name: 'finished_at' },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'url_certificate',
                        name: 'url_certificate',
                        orderable: false,
                        searchable: false
                    },
                ],
                language: {
                    paginate: {
                        next: '&#8594;',
                        previous: '&#8592;'
                    },
                    processing: "Memuat...",
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data tersedia",
                    zeroRecords: "Tidak ada data yang sesuai",
                }
            });

            async function syncAction(url, btnId, label) {
                const btn = $(btnId);
                const icon = btnId === '#btnSyncPush' ? '<i class="ti ti-upload me-1"></i>' : '<i class="ti ti-download me-1"></i>';
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + label + '...');

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const data = await response.json();

                    if (response.ok) {
                        toastr.success(data.message || label + ' berhasil.');
                    } else {
                        toastr.warning(data.message || label + ' selesai dengan error.');
                    }

                    table.ajax.reload(null, false);

                } catch (err) {
                    console.error(label + ' error:', err);
                    toastr.error('Terjadi kesalahan. Periksa konsol browser.');
                } finally {
                    // Pastikan spinner SELALU berhenti
                    btn.prop('disabled', false).html(icon + label);
                }
            }

            $('#btnSyncPush').on('click', function() {
                syncAction('{{ route('admin.certificate.sync-push') }}', '#btnSyncPush', 'Sync Push');
            });

            $('#btnSyncPull').on('click', function() {
                syncAction('{{ route('admin.certificate.sync-pull') }}', '#btnSyncPull', 'Sync Pull');
            });
        });
    </script>
@endpush

