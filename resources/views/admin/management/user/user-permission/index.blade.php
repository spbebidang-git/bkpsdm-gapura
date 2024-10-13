@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            User Permission Setting
        </h5>
        <form action="{{ route('admin.management.user.user-permission.store', request()->user) }}" method="post">
            @csrf
            <div class="card-body p-4 overflow-hidden border-bottom">
                <div class="row">
                    @forelse ($groupPermissions as $item)
                        <div class="col-3">
                            <h6 class="mb-2">{{ $item->name }}</h6>
                            @foreach ($item->permissions as $permission)
                                <div class="form-check">
                                    <input name="permissions[]" class="form-check-input" type="checkbox"
                                        value="{{ $permission->id }}" id="{{ $permission->id }}"
                                        {{ $userPermissions->contains($permission->id) ? 'checked' : '' }} />
                                    <label class="form-label"class="form-check-label"
                                        for="{{ $permission->id }}">{{ $permission->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <h6 class="mb-0 text-center text-capitalize">No data available.</h6>
                    @endforelse
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    @if ($groupPermissions->count() > 0)
                        <button class="btn btn-primary">Save</button>
                    @endif
                    <a href="{{ route('admin.management.user.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </form>
    </div>
@endsection
