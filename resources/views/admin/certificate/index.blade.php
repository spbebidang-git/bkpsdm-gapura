@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Daftar Sertifikat</span>
            <div class="d-flex gap-2">
                <button class="btn btn-warning btn-sm" id="btnSyncPush" title="Kirim data peserta ke SSO">
                    <i class="ti ti-upload me-1"></i>Sync Push
                </button>
                <button class="btn btn-info btn-sm text-white" id="btnSyncPull" title="Tarik/Cek semua URL sertifikat">
                    <i class="ti ti-download me-1"></i>Sync Pull
                </button>
                <button class="btn btn-dark btn-sm text-white" id="btnSyncPullUpdateDomain" title="Tarik data khusus yang sertifikatnya sudah tersedia untuk mengubah domain URL lama menjadi baru">
                    <i class="ti ti-reload me-1"></i>Update Domain Lama
                </button>
            </div>
        </h5>

        {{-- Filters --}}
        <div class="px-4 pt-3 pb-2">
            <div class="row g-3 align-items-end">
                {{-- Course Filter --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-1"><i class="ti ti-book me-1"></i>Kelas</label>
                    <select class="form-select form-select-sm" id="filterCourse">
                        <option value="">Semua Kelas</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold mb-1"><i class="ti ti-calendar me-1"></i>Dari Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="filterDateFrom">
                </div>

                {{-- Date To --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold mb-1"><i class="ti ti-calendar me-1"></i>Sampai Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="filterDateTo">
                </div>

                {{-- Reset --}}
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-sm w-100" id="btnResetFilter">
                        <i class="ti ti-refresh me-1"></i>Reset Filter
                    </button>
                </div>
            </div>
        </div>

        {{-- Status Filter --}}
        <div class="px-4 pt-2 pb-1 d-flex flex-wrap gap-2 align-items-center">
            <small class="text-muted fw-semibold me-1">Filter Status:</small>
            <span class="badge bg-secondary status-filter active" data-filter="" role="button" style="cursor:pointer">Semua</span>
            <span class="badge bg-warning text-dark status-filter" data-filter="menunggu" role="button" style="cursor:pointer">Menunggu Sync</span>
            <span class="badge bg-info text-dark status-filter" data-filter="dikirim" role="button" style="cursor:pointer">Dikirim ke SSO</span>
            <span class="badge bg-success status-filter" data-filter="tersedia" role="button" style="cursor:pointer">Sertifikat Tersedia</span>
        </div>

        <div class="card-body p-0 overflow-hidden">
            <div class="table-responsive text-wrap overflow-hidden p-4">
                <table class="table table-bordered text-center" id="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th class="text-center">Nama</th>
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
    <style nonce="{{ Vite::cspNonce() }}">
        .status-filter { opacity: 0.5; transition: all 0.2s; font-size: 0.85rem; padding: 0.4em 0.8em; }
        .status-filter:hover { opacity: 0.8; transform: scale(1.05); }
        .status-filter.active { opacity: 1; transform: scale(1.1); box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
    </style>
    <script nonce="{{ Vite::cspNonce() }}">
        $(document).ready(function() {
            let currentStatusFilter = '';

            const table = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ url()->current() }}',
                    data: function(d) {
                        d.status_filter = currentStatusFilter;
                        d.course_filter = $('#filterCourse').val();
                        d.date_from = $('#filterDateFrom').val();
                        d.date_to = $('#filterDateTo').val();
                    }
                },
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
                    {
                        data: 'satker',
                        name: 'satker',
                        orderable: false,
                        searchable: false
                    },
                    { data: 'course', name: 'course' },
                    {
                        data: 'finished_at',
                        name: 'finished_at',
                        searchable: false
                    },
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

            // Status filter click handler
            $('.status-filter').on('click', function() {
                $('.status-filter').removeClass('active');
                $(this).addClass('active');
                currentStatusFilter = $(this).data('filter');
                table.ajax.reload();
            });

            // Course & date filter change handler
            $('#filterCourse, #filterDateFrom, #filterDateTo').on('change', function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#btnResetFilter').on('click', function() {
                $('#filterCourse').val('');
                $('#filterDateFrom').val('');
                $('#filterDateTo').val('');
                currentStatusFilter = '';
                $('.status-filter').removeClass('active');
                $('.status-filter[data-filter=""]').addClass('active');
                table.ajax.reload();
            });

            // Sync action helper
            async function syncAction(url, btnId, label, customPayload = null) {
                const btn = $(btnId);
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

                // Include course_id if filtered, unless customPayload overrides
                let payload = customPayload;
                if (!payload) {
                    const courseId = $('#filterCourse').val();
                    payload = courseId ? { course_id: courseId } : {};
                }

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: data.message || label + ' berhasil.',
                            confirmButtonText: 'Tutup',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Peringatan',
                            text: data.message || label + ' selesai dengan error.',
                            confirmButtonText: 'Tutup',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                    }

                    table.ajax.reload(null, false);

                } catch (err) {
                    console.error(label + ' error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan. Periksa konsol browser.',
                        confirmButtonText: 'Tutup',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                } finally {
                    btn.prop('disabled', false).html(originalHtml);
                }
            }

            // Bulk Push Header
            $('#btnSyncPush').on('click', function() {
                const courseName = $('#filterCourse option:selected').text().trim();
                const courseId = $('#filterCourse').val();

                let confirmText = 'Push semua sertifikat yang belum dikirim ke SSO?';
                if (courseId) {
                    confirmText = 'Push sertifikat untuk kelas "' + courseName + '" ke SSO?';
                }

                Swal.fire({
                    icon: 'question',
                    title: 'Konfirmasi Sync Push',
                    text: confirmText,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Push',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-warning me-2',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        syncAction('{{ route('admin.certificate.sync-push') }}', '#btnSyncPush', 'Sync Push');
                    }
                });
            });

            $('#btnSyncPull').on('click', function() {
                const courseName = $('#filterCourse option:selected').text().trim();
                const courseId = $('#filterCourse').val();

                let confirmText = 'Pull semua URL sertifikat dari SSO?';
                if (courseId) {
                    confirmText = 'Pull URL sertifikat untuk kelas "' + courseName + '" dari SSO?';
                }

                Swal.fire({
                    icon: 'question',
                    title: 'Konfirmasi Sync Pull',
                    text: confirmText,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Pull',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-info me-2',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        syncAction('{{ route('admin.certificate.sync-pull') }}', '#btnSyncPull', 'Sync Pull');
                    }
                });
            });

            // Pull khusus update domain
            $('#btnSyncPullUpdateDomain').on('click', function() {
                const courseName = $('#filterCourse option:selected').text().trim();
                const courseId = $('#filterCourse').val();

                let confirmText = 'Aksi ini hanya memeriksa dan mer-replace URL pada sertifikat yang sudah berstatus Tersedia.\n\nLanjutkan Update URL Lama?';
                if (courseId) {
                    confirmText = 'Aksi ini hanya memeriksa dan mer-replace URL kelas "' + courseName + '" yang sudah berstatus Tersedia.\n\nLanjutkan Update?';
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Update Domain Lama',
                    text: confirmText,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Update Domain',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-dark me-2 text-white',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let payload = { only_has_url: true };
                        if (courseId) payload.course_id = courseId;
                        syncAction('{{ route('admin.certificate.sync-pull') }}', '#btnSyncPullUpdateDomain', 'Update Domain', payload);
                    }
                });
            });
        });
    </script>
@endpush
