@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.instructor.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
        @csrf

        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Tambah Instruktur
            </h5>

            <div class="card-body border-bottom p-4">

                {{-- Nama --}}
                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama Instruktur <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        id="name" placeholder="John Doe" value="{{ old('name') }}" autocomplete="off">

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
                        id="email" placeholder="contoh: instruktur@mail.com" value="{{ old('email') }}"
                        autocomplete="new-email">

                    <small class="text-muted d-block mt-1">
                        Masukkan email aktif instruktur. Email ini juga digunakan untuk login.
                    </small>

                    @error('email')
                        <small class="invalid-feedback d-block">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-3 form-password-toggle">
                    <label for="password" class="form-label">
                        Password <span class="text-danger">*</span>
                    </label>

                    <div class="input-group input-group-merge">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"
                            id="password" placeholder="Min. 8 karakter" autocomplete="new-password">
                        <span class="input-group-text cursor-pointer">
                            <i class="bx bx-hide"></i>
                        </span>

                        @error('password')
                            <small class="invalid-feedback d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    <small class="text-muted d-block mt-1">
                        Gunakan kombinasi huruf & angka (minimal 8 karakter).
                    </small>
                </div>


                {{-- Foto --}}
                <div class="mb-3">
                    <label for="image" class="form-label">
                        Foto Instruktur (Opsional)
                    </label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" name="image"
                        id="image">

                    @error('image')
                        <small class="invalid-feedback d-block">{{ $message }}</small>
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

    {{-- Show Password Script --}}
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;

            this.innerHTML = type === 'password' ?
                '<i class="bi bi-eye"></i>' :
                '<i class="bi bi-eye-slash"></i>';
        });
    </script>
@endsection
