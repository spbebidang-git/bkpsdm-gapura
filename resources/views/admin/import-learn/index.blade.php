@extends('admin.layouts.app')

@section('breadcrumb', 'Import Learn')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <span><i class="bx bx-import me-2"></i>Import Data Learn</span>
                    <a href="{{ route('admin.import-learn.template') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-download me-1"></i>Download Template
                    </a>
                </h5>

                <div class="card-body">
                    {{-- Import Results --}}
                    @if (Session::has('import_results'))
                        @php $results = Session::get('import_results'); @endphp
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading fw-bold mb-2">
                                <i class="bx bx-check-circle me-1"></i>Hasil Import — {{ Session::get('import_course') }}
                            </h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <span class="d-block fs-4 fw-bold text-success">{{ $results['success'] }}</span>
                                    <small class="text-muted">Berhasil</small>
                                </div>
                                <div class="col-4">
                                    <span class="d-block fs-4 fw-bold text-warning">{{ $results['skipped'] }}</span>
                                    <small class="text-muted">Dilewati</small>
                                </div>
                                <div class="col-4">
                                    <span class="d-block fs-4 fw-bold text-danger">{{ $results['failed'] }}</span>
                                    <small class="text-muted">Gagal</small>
                                </div>
                            </div>

                            @if (!empty($results['errors']))
                                <hr>
                                <details>
                                    <summary class="fw-bold cursor-pointer">
                                        <i class="bx bx-info-circle me-1"></i>Detail ({{ count($results['errors']) }} catatan)
                                    </summary>
                                    <ul class="mt-2 mb-0 small">
                                        @foreach ($results['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    @endif

                    <form action="{{ route('admin.import-learn.store') }}" method="POST" enctype="multipart/form-data"
                        id="importForm">
                        @csrf

                        {{-- Course Selection --}}
                        <div class="mb-3">
                            <label for="course_id" class="form-label">
                                Kursus <span class="text-danger">*</span>
                            </label>
                            <select name="course_id" id="course_id"
                                class="form-select @error('course_id') is-invalid @enderror" required>
                                <option value="">— Pilih Kursus —</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}"
                                        {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Finished At --}}
                        <div class="mb-3">
                            <label for="finished_at" class="form-label">
                                Tanggal Selesai Pelatihan <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="finished_at" id="finished_at"
                                class="form-control @error('finished_at') is-invalid @enderror"
                                value="{{ old('finished_at') }}" required>
                            @error('finished_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Tanggal pelatihan Zoom dilaksanakan.</div>
                        </div>

                        {{-- File Upload --}}
                        <div class="mb-3">
                            <label for="file" class="form-label">
                                File Excel <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="file" id="file"
                                class="form-control @error('file') is-invalid @enderror"
                                accept=".xlsx,.xls,.csv" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Format: .xlsx, .xls, .csv — Maksimal 5MB</div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" id="btnImport">
                                <i class="bx bx-upload me-1"></i>Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Panel --}}
        <div class="col-md-4">
            <div class="card">
                <h6 class="card-header border-bottom">
                    <i class="bx bx-info-circle me-1"></i>Panduan Import
                </h6>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Tujuan:</strong>
                        <p class="text-muted small mb-0">
                            Import data peserta yang sudah menyelesaikan pelatihan melalui Zoom
                            tetapi belum tercatat di sistem karena server down.
                        </p>
                    </div>

                    <div class="mb-3">
                        <strong>Format Excel:</strong>
                        <div class="table-responsive mt-1">
                            <table class="table table-sm table-bordered small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>nip</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>123456789012345678</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Apa yang terjadi:</strong>
                        <ul class="small text-muted mb-0">
                            <li>Sistem mencari peserta berdasarkan <strong>NIP</strong></li>
                            <li>Membuat record <code>learns</code> dan semua <code>learn_items</code> untuk kursus yang dipilih</li>
                            <li>Semua activity ditandai sebagai <strong>done</strong></li>
                            <li>Peserta yang sudah menyelesaikan kursus akan <strong>di-skip</strong></li>
                        </ul>
                    </div>

                    <div class="alert alert-warning small mb-0">
                        <i class="bx bx-error-circle me-1"></i>
                        <strong>Perhatian:</strong> Pastikan data NIP sudah benar sebelum import.
                        Import diproses di background — refresh halaman untuk melihat hasil.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ Vite::cspNonce() }}">
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('btnImport');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses...';
        });
    </script>
@endpush
