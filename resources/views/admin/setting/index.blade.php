@extends('admin.layouts.app')

@section('breadcrumb', 'Setting')

@section('content')
    <form action="{{ route('admin.management.setting.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card p-0">
            <h5 class="card-header border-bottom">
                Site Settings
            </h5>
            <div class="card-body border-bottom p-4">
                {{-- site_title --}}
                <div class="mb-3">
                    <label for="site_title" class="form-label">site title</label>
                    <input type="text" class="form-control @error('site_title') is-invalid @enderror" name="site_title"
                        id="site_title" value="{{ old('site_title') ?? get_setting_meta('site_title') }}">
                    @error('site_title')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                {{-- site_description --}}
                <div class="mb-3">
                    <label for="site_description" class="form-label">site description</label>
                    <input type="text" class="form-control @error('site_description') is-invalid @enderror"
                        name="site_description" id="site_description"
                        value="{{ old('site_description') ?? get_setting_meta('site_description') }}">
                    @error('site_description')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                {{-- site_logo --}}
                <div class="mb-3">
                    <label for="site_logo" class="form-label">site logo</label>
                    <input type="file" class="form-control @error('site_logo') is-invalid @enderror" name="site_logo"
                        id="site_logo">
                    <small class="fw-italic">Please empty blank if not changed.</small>
                    @error('site_logo')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

            </div>
            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </form>
@endsection
