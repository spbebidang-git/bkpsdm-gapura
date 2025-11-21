@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.help.update', $help->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card p-0">
            <h5 class="card-header border-bottom">Edit Help (Q&A)</h5>
            <div class="card-body border-bottom p-4">
                <div class="mb-3">
                    <label for="question" class="form-label">Question</label>
                    <input type="text" class="form-control @error('question') is-invalid @enderror" name="question"
                        id="question" value="{{ old('question', $help->question) }}">
                    @error('question')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="answer" class="form-label">Answer</label>
                    <textarea class="form-control @error('answer') is-invalid @enderror" name="answer" id="answer" rows="6">{{ old('answer', $help->answer) }}</textarea>
                    @error('answer')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" name="is_active" id="is_active"
                        {{ $help->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Update</button>
                    <a href="{{ route('admin.help.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </div>
    </form>
@endsection
