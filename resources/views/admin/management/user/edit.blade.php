@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.management.user.update', $user->id) }}" method="POST">
        @csrf
        @method('put')

        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Edit Pengguna
            </h5>

            <div class="card-body border-bottom p-4">

                {{-- name --}}
                <div class="mb-3">
                    <label for="name" class="form-label">Nama</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        id="name" value="{{ old('name', $user->name) }}">
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                {{-- email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" class="form-control @error('email') is-invalid @enderror" name="email"
                        id="email" value="{{ old('email', $user->email) }}">
                    @error('email')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                {{-- password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="text" class="form-control @error('password') is-invalid @enderror" name="password"
                        id="password" value="{{ old('password') }}">
                    @error('password')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                    <small class="text-muted">Kosongkan jika tidak ingin mengubah.</small>
                </div>

                {{-- roles --}}
                <div class="mb-3">
                    <label for="role" class="form-label">Peran</label>
                    <select name="role" id="role" class="form-select">
                        <option value="">Pilih Peran</option>
                        @foreach ($roles as $item)
                            <option value="{{ $item->name }}" {{ $user->hasRole($item->name) ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('admin.management.user.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </div>
        </div>

    </form>
@endsection
