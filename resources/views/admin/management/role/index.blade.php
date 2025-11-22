@extends('admin.layouts.app')

@section('content')
    <div class="card">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Roles
        </h5>

        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered" id="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
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
                stateSave: true,
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
                ],
                language: {
                    paginate: {
                        next: '&#8594;',
                        previous: '&#8592;'
                    }
                }
            });
        });

        const confirmDelete = (event) => {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to delete this data?',
                text: "Deleted data cannot be recovered!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        };
    </script>
@endpush
