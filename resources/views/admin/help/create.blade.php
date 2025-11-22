@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('admin.help.store') }}" method="POST">
        @csrf
        <div class="card p-0">
            <h5 class="card-header border-bottom">Tambah Bantuan (Q&A)</h5>

            <div class="card-body border-bottom p-4">
                <div class="mb-3">
                    <label for="question" class="form-label">Pertanyaan</label>
                    <input type="text" class="form-control @error('question') is-invalid @enderror" name="question"
                        id="question" value="{{ old('question') }}">
                    @error('question')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="answer" class="form-label">Jawaban</label>
                    <textarea class="form-control @error('answer') is-invalid @enderror" name="answer" id="answer" rows="6">{{ old('answer') }}</textarea>
                    @error('answer')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" name="is_active" id="is_active" checked>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('admin.help.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </form>
@endsection
