@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.instructor.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Tambah Instruktur
            </h5>

            <div class="card-body border-bottom p-4">

                {{-- Nama --}}
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Instruktur</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        id="name" placeholder="Masukkan nama instruktur" value="{{ old('name') }}">
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" class="form-control @error('email') is-invalid @enderror" name="email"
                        id="email" placeholder="Masukkan email instruktur" value="{{ old('email') }}">
                    @error('email')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"
                        id="password" placeholder="Masukkan password">
                    @error('password')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Foto --}}
                <div class="mb-3">
                    <label for="image" class="form-label">Foto Instruktur</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" name="image"
                        id="image">
                    @error('image')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
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
