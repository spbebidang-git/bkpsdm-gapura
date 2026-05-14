@extends('admin.layouts.app')

@section('breadcrumb', 'Manajemen Review')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Daftar Ulasan
        </h5>

        <div class="card-body p-0 overflow-hidden">
            <div class="table-responsive text-wrap overflow-hidden p-4">
                <table class="table table-bordered text-center" id="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th>Kursus</th>
                            <th>Pengguna</th>
                            <th>Rating</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0"></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ Vite::cspNonce() }}">
        $(document).ready(function() {
            $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ url()->current() }}',
                stripeClasses: [],
                columnDefs: [{
                    className: "dt-center",
                    targets: "_all"
                }],
                bPaginate: true,
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '10px'
                    },
                    {
                        data: 'course',
                        name: 'course'
                    },
                    {
                        data: 'user',
                        name: 'user'
                    },
                    {
                        data: 'star',
                        name: 'star'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'action',
                        name: 'action',
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
        });

        // Swal delete
        $(document).on('submit', '.form-delete', function(event) {
            event.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Yakin ingin menghapus ulasan ini?',
                text: "Data yang sudah dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endpush
