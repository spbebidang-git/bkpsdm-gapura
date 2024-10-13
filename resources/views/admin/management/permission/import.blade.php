@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="card col-6">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Import Permission
            <div class="d-flex gap-2">
                <a class="btn btn-success btn-sm" href="{{ route('admin.area.import.template-export') }}">Export Template</a>
            </div>
        </h5>
        <form action="{{ route('admin.management.permission.store-import') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-body border-bottom p-4">
                <div class="mb-3">
                    <input type="file" class="form-control @error('file') is-invalid @enderror" name="file"
                        id="file" value="{{ old('file') }}">
                    @error('file')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Import</button>
                    <a href="{{ route('admin.management.permission.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // select2
        $('.select2').select2({
            theme: 'bootstrap-5',
        });
    </script>
@endpush
