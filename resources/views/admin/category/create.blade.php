@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.category.store') }}" method="POST">
        @csrf
        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Add Category
            </h5>
            <div class="card-body border-bottom p-4">
                {{-- title --}}
                <div class="mb-3">
                    <label for="title" class="form-label">title</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" name="title"
                        id="title" value="{{ old('title') }}">
                    @error('title')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                {{-- is_primary --}}
                <div class="mb-3">
                    <label for="password" class="form-label">is primary</label>
                    <select name="is_primary" id="is_primary" class="form-select">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                    @error('is_primary')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.category.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </div>
    </form>
@endsection
