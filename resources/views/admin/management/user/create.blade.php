@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.management.user.index') }}" method="POST">
        @csrf
        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Add User
            </h5>
            <div class="card-body border-bottom p-4">
                {{-- name --}}
                <div class="mb-3">
                    <label for="name" class="form-label">name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        id="name" value="{{ old('name') }}">
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                {{-- email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">email</label>
                    <input type="text" class="form-control @error('email') is-invalid @enderror" name="email"
                        id="email" value="{{ old('email') }}">
                    @error('email')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                {{-- password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">password</label>
                    <input type="text" class="form-control @error('password') is-invalid @enderror" name="password"
                        id="password" value="{{ old('password') }}">
                    @error('password')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                {{-- roles --}}
                <div class="mb-3">
                    <label for="password" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="">Select Role</option>
                        @foreach ($roles as $item)
                            <option value="{{ $item->name }}">
                                {{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('password')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.management.user.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </div>
    </form>
@endsection
