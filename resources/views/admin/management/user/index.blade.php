@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Pengguna
            <a class="btn btn-primary btn-sm" href="{{ route('admin.management.user.create') }}">
                <i class="bx bx-plus"></i>
            </a>
        </h5>

        <div class="card-body p-0 overflow-hidden">
            <div class="table-responsive text-wrap overflow-hidden p-4">
                <table class="table table-bordered text-center" id="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">Peran</th>
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
                        width: '10px',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'role',
                        name: 'role'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '30px'
                    },
                ],
                language: {
                    paginate: {
                        next: '›',
                        previous: '‹'
                    }
                }
            });
        });

        $(document).on('submit', '.form-delete', function(event) {
            event.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Apakah Anda yakin ingin menghapus data ini?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    container: 'swal2-container'
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endpush
