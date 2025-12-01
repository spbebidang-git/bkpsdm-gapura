@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.instructor.update', $instructor->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')

        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Edit Instruktur
            </h5>

            <div class="card-body border-bottom p-4">

                {{-- Nama --}}
                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama Instruktur <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        id="name" placeholder="John Doe" value="{{ old('name') ?? $instructor->name }}">
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">
                        Email <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control @error('email') is-invalid @enderror" name="email"
                        id="email" placeholder="yourmail@example.com" value="{{ old('email') ?? $instructor->email }}">
                    @error('email')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Password (Opsional) --}}
                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password (Opsional)
                    </label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"
                        id="password" placeholder="Kosongkan jika tidak ingin mengubah">

                    @error('password')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror

                    <small class="text-muted d-block">
                        Gunakan kombinasi huruf dan angka (min. 8 karakter).
                    </small>
                </div>

                {{-- Foto (Opsional) --}}
                <div class="mb-3">
                    <label for="image" class="form-label">
                        Foto Instruktur (Opsional)
                    </label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" name="image"
                        id="image">

                    @error('image')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror

                    <small class="text-muted d-block">
                        Maksimal ukuran file 1 MB. Format diperbolehkan: JPG, PNG.
                    </small>
                </div>

            </div>

            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('admin.instructor.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </div>

        </div>
    </form>
@endsection
