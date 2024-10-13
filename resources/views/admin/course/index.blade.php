@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Course Lists
        </h5>
        <div class="card-body p-0 overflow-hidden">
            <div class="table-responsive text-wrap overflow-hidden p-4">
                <table class="table table-bordered text-center" id="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th class="text-center">Image</th>
                            <th class="text-center">Category</th>
                            <th class="text-center">Title</th>
                            <th class="text-center">Instructor</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Action</th>
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
                        data: 'image',
                        name: 'image',
                    },
                    {
                        data: 'category',
                        name: 'category',
                    },
                    {
                        data: 'title',
                        name: 'title',
                    },
                    {
                        data: 'instructor',
                        name: 'instructor',
                    },
                    {
                        data: 'type',
                        name: 'type',
                    },
                    {
                        data: 'action',
                        name: 'action',
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

        // swal delete
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
                customClass: {
                    container: 'swal2-container',
                },

            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        };
    </script>
@endpush
