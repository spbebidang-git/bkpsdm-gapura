@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.category.update', $category->id) }}" method="POST">
        @csrf
        @method('put')
        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Edit Kategori
            </h5>
            <div class="card-body border-bottom p-4">
                {{-- title --}}
                <div class="mb-3">
                    <label for="title" class="form-label">Judul</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" name="title"
                        id="title" value="{{ old('title') ?? $category->title }}">
                    @error('title')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                {{-- is_primary --}}
                <div class="mb-3">
                    <label for="is_primary" class="form-label">Kategori Utama</label>
                    <select name="is_primary" id="is_primary" class="form-select">
                        <option value="0" {{ $category->is_primary == 0 ? 'selected' : '' }}>Tidak</option>
                        <option value="1" {{ $category->is_primary == 1 ? 'selected' : '' }}>Ya</option>
                    </select>
                    @error('is_primary')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('admin.category.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </form>
@endsection
