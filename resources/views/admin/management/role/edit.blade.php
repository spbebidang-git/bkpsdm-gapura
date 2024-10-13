@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.management.role.update', $role->id) }}" method="POST">
        @csrf
        @method('put')
        <div class="card p-0">
            <h5 class="card-header border-bottom text-capitalize">
                Edit role
            </h5>
            <div class="card-body border-bottom p-4">
                {{-- name --}}
                <div class="mb-3">
                    <label for="name" class="form-label">name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        id="name" value="{{ old('name') ?? $role->name }}">
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                {{-- permissions --}}
                <div class="mb-3">
                    <label for="name" class="form-label">Permissions</label>
                    <select name="permissions[]" id="permissions" multiple class="form-select select2">
                        @foreach ($allPermissions as $item)
                            <option {{ $rolePermissions->contains($item->id) ? 'selected' : '' }}
                                value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.management.role.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </div>
    </form>
@endsection
