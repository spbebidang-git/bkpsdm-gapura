@extends('admin.layouts.app')

@section('content')
    <div class="">

        <!-- Statistik Ringkas -->
        <div class="row">

            <!-- Total Kursus -->
            <div class="col-lg-3 col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <span class="fw-medium d-block mb-1">Total Kursus</span>
                        <h3 class="card-title mb-2">{{ $totalCourses }}</h3>
                        <small class="text-muted">kursus aktif</small>
                    </div>
                </div>
            </div>

            <!-- Total Peserta -->
            <div class="col-lg-3 col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <span class="fw-medium d-block mb-1">Total Peserta</span>
                        <h3 class="card-title mb-2">{{ number_format($totalParticipants, 0, ',', '.') }}</h3>
                        <small class="text-muted">aktif mengikuti kursus</small>
                    </div>
                </div>
            </div>

            <!-- Peserta Selesai -->
            <div class="col-lg-3 col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <span class="fw-medium d-block mb-1">Pelatihan Selesai</span>
                        <h3 class="card-title mb-2">{{ number_format($totalFinished, 0, ',', '.') }}</h3>
                        <small class="text-muted">menyelesaikan semua kursus</small>
                    </div>
                </div>
            </div>

            <!-- Total Instruktur -->
            <div class="col-lg-3 col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <span class="fw-medium d-block mb-1">Total Instruktur</span>
                        <h3 class="card-title mb-2">{{ $totalInstructors }}</h3>
                        <small class="text-muted">aktif memberikan pelatihan</small>
                    </div>
                </div>
            </div>

        </div>

        <!-- Tabel Kursus Terpopuler -->
        <div class="card p-0 mb-4">
            <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
                Kursus Terpopuler
            </h5>

            <div class="card-body p-0 overflow-hidden">
                <div class="table-responsive p-4">
                    <table class="table table-bordered text-center align-middle" id="table-popular-courses">
                        <thead>
                            <tr class="text-nowrap">
                                <th>#</th>
                                <th class="text-center">Nama Kursus</th>
                                <th class="text-center">Jumlah Peserta</th>
                                <th class="text-center">Tuntas (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($popularCourses as $index => $course)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $course['name'] }}</strong></td>
                                    <td>{{ $course['participants'] }}</td>
                                    <td>
                                        <span class="badge {{ $course['badge'] }} me-1">
                                            {{ $course['completion'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ─── PERFORMA MARGIN KURSUS ─── --}}
    <div class="card border-0 shadow-sm p-0">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3 px-4">
            <h6 class="mb-0 fw-bold">📊 Performa Margin Kursus</h6>
            <a href="{{ route('admin.course-performance.index') }}" class="btn btn-sm btn-outline-primary">
                Lihat Detail →
            </a>
        </div>

        <div class="card-body p-4">

            {{-- Summary Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #6366f1 !important;">
                        <div class="card-body py-3 px-4">
                            <p class="text-muted small mb-1">Total Materi</p>
                            <div class="fs-2 fw-bold text-dark">{{ count($result) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important;">
                        <div class="card-body py-3 px-4">
                            <p class="text-muted small mb-1">Rata-rata Pre Test</p>
                            <div class="fs-2 fw-bold" style="color:#f59e0b">
                                {{ count($result) ? round($result->avg('avg_pre_test'), 1) : 0 }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
                        <div class="card-body py-3 px-4">
                            <p class="text-muted small mb-1">Rata-rata Post Test</p>
                            <div class="fs-2 fw-bold" style="color:#10b981">
                                {{ count($result) ? round($result->avg('avg_post_test'), 1) : 0 }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #3b82f6 !important;">
                        <div class="card-body py-3 px-4">
                            <p class="text-muted small mb-1">Rata-rata Margin</p>
                            <div class="fs-2 fw-bold" style="color:#3b82f6">
                                {{ count($result) ? round($result->avg('avg_margin'), 1) : 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabel Ringkas --}}
            @if(count($result) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-3 py-3 fw-semibold text-muted" style="width:40px">#</th>
                            <th class="py-3 fw-semibold text-muted">Nama Materi</th>
                            <th class="py-3 fw-semibold text-muted text-center">Peserta</th>
                            <th class="py-3 fw-semibold text-muted text-center" style="color:#f59e0b!important">Pre Test</th>
                            <th class="py-3 fw-semibold text-muted text-center" style="color:#10b981!important">Post Test</th>
                            <th class="py-3 fw-semibold text-muted text-center">Margin</th>
                            <th class="pe-3 py-3 fw-semibold text-muted text-center">Efektivitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($result as $i => $row)
                            @php
                                $margin    = $row['avg_margin'];
                                $efek      = $row['peserta_mengikuti'] > 0
                                    ? round(($row['peserta_selesai'] / $row['peserta_mengikuti']) * 100, 1)
                                    : 0;
                                $marginColor = $margin >= 20 ? '#10b981' : ($margin >= 0 ? '#f59e0b' : '#ef4444');
                                $marginIcon  = $margin > 0 ? '▲' : ($margin == 0 ? '─' : '▼');
                                $efekColor   = $efek >= 70 ? 'success' : ($efek >= 40 ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                <td><span class="fw-semibold text-dark">{{ $row['course_title'] }}</span></td>
                                <td class="text-center">
                                    <span class="fw-semibold">{{ $row['peserta_selesai'] }}</span>
                                    <span class="text-muted">/{{ $row['peserta_mengikuti'] }}</span>
                                </td>
                                <td class="text-center fw-bold" style="color:#f59e0b">{{ $row['avg_pre_test'] }}</td>
                                <td class="text-center fw-bold" style="color:#10b981">{{ $row['avg_post_test'] }}</td>
                                <td class="text-center fw-bold" style="color:{{ $marginColor }}">
                                    {{ $marginIcon }} {{ abs($margin) }}
                                </td>
                                <td class="pe-3 text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px;max-width:80px">
                                            <div class="progress-bar bg-{{ $efekColor }}" style="width:{{ $efek }}%"></div>
                                        </div>
                                        <span class="badge text-bg-{{ $efekColor }} rounded-pill">{{ $efek }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-4">
                <div class="fs-1 mb-2">📭</div>
                <p class="text-muted small mb-0">Belum ada data performa. Data akan muncul setelah peserta menyelesaikan pelatihan beserta pre &amp; post test-nya.</p>
            </div>
            @endif

        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ Vite::cspNonce() }}">
        $(document).ready(function() {
            $('#table-popular-courses').DataTable({
                pageLength: 5,
                lengthChange: false,
                searching: false,
                ordering: true,
                info: false,
                language: {
                    paginate: {
                        previous: "‹",
                        next: "›"
                    },
                    emptyTable: "Tidak ada data tersedia",
                },
                columnDefs: [{
                    orderable: false,
                    targets: 3 // kolom badge
                }]
            });
        });
    </script>
@endpush



