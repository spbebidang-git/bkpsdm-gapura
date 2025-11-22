@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.management.permission.update', $permission->id) }}" method="POST">
        @csrf
        @method('put')
        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Edit Izin Akses
            </h5>

            <div class="card-body border-bottom p-4">
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Izin</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        id="name" value="{{ old('name', $permission->name) }}">
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('admin.management.permission.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </form>
@endsection
