@extends('admin.layouts.app')

@section('breadcrumb', 'Performa Materi')

@section('content')

{{-- ─── SUMMARY CARDS ─── --}}
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

@if(count($result) > 0)

{{-- ─── TABEL DATA ─── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3 px-4">
        <h6 class="mb-0 fw-bold">📋 Data Performa Materi</h6>
        <span class="badge rounded-pill text-bg-secondary">{{ count($result) }} materi</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                <thead style="background:#f8fafc">
                    <tr>
                        <th class="ps-4 py-3 fw-semibold text-muted" style="width:40px">#</th>
                        <th class="py-3 fw-semibold text-muted">Nama Materi</th>
                        <th class="py-3 fw-semibold text-muted text-center">Peserta</th>
                        <th class="py-3 fw-semibold text-muted text-center" style="color:#f59e0b!important">Pre Test</th>
                        <th class="py-3 fw-semibold text-muted text-center" style="color:#10b981!important">Post Test</th>
                        <th class="py-3 fw-semibold text-muted text-center">Margin</th>
                        <th class="pe-4 py-3 fw-semibold text-muted text-center">Efektivitas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result as $i => $row)
                        @php
                            $margin = $row['avg_margin'];
                            $efek = $row['peserta_mengikuti'] > 0
                                ? round(($row['peserta_selesai'] / $row['peserta_mengikuti']) * 100, 1)
                                : 0;

                            $marginColor  = $margin >= 20 ? '#10b981' : ($margin >= 0 ? '#f59e0b' : '#ef4444');
                            $marginIcon   = $margin > 0 ? '▲' : ($margin == 0 ? '─' : '▼');
                            $efekColor    = $efek >= 70 ? 'success' : ($efek >= 40 ? 'warning' : 'danger');
                        @endphp
                        <tr>
                            <td class="ps-4 text-muted">{{ $i + 1 }}</td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $row['course_title'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ $row['peserta_selesai'] }}</span>
                                <span class="text-muted">/{{ $row['peserta_mengikuti'] }}</span>
                                <div class="text-muted" style="font-size:0.75rem">Selesai/Daftar</div>
                            </td>
                            <td class="text-center fw-bold" style="color:#f59e0b">
                                {{ $row['avg_pre_test'] }}
                            </td>
                            <td class="text-center fw-bold" style="color:#10b981">
                                {{ $row['avg_post_test'] }}
                            </td>
                            <td class="text-center fw-bold" style="color:{{ $marginColor }}">
                                {{ $marginIcon }} {{ abs($margin) }}
                            </td>
                            <td class="pe-4 text-center">
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
    </div>
</div>

{{-- ─── CARD DETAIL PER MATERI ─── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="mb-0 fw-bold">📝 Detail Analisis Per Materi</h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            @foreach ($result as $row)
                @php
                    $efek = $row['peserta_mengikuti'] > 0
                        ? round(($row['peserta_selesai'] / $row['peserta_mengikuti']) * 100, 1)
                        : 0;
                    $efekColor = $efek >= 70 ? '#10b981' : ($efek >= 40 ? '#f59e0b' : '#ef4444');
                    $marginColor = $row['avg_margin'] >= 20 ? '#10b981' : ($row['avg_margin'] >= 0 ? '#f59e0b' : '#ef4444');
                @endphp
                <div class="col-md-6">
                    <div class="rounded-3 p-4 h-100" style="border:1px solid #e5e7eb; background:#fafafa;">

                        {{-- Judul --}}
                        <div class="fw-bold mb-3 text-dark" style="font-size:0.95rem">
                            📘 {{ $row['course_title'] }}
                        </div>

                        {{-- Skor 3 kolom --}}
                        <div class="row text-center mb-3 g-0">
                            <div class="col-4">
                                <div class="text-muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.03em">Pre Test</div>
                                <div class="fw-bold fs-4" style="color:#f59e0b">{{ $row['avg_pre_test'] }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.03em">Post Test</div>
                                <div class="fw-bold fs-4" style="color:#10b981">{{ $row['avg_post_test'] }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.03em">Margin</div>
                                <div class="fw-bold fs-4" style="color:{{ $marginColor }}">
                                    {{ $row['avg_margin'] >= 0 ? '+' : '' }}{{ $row['avg_margin'] }}
                                </div>
                            </div>
                        </div>

                        {{-- Progress efektivitas --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size:0.8rem">
                                <span class="text-muted">Efektivitas Peserta</span>
                                <span class="fw-semibold" style="color:{{ $efekColor }}">{{ $efek }}%</span>
                            </div>
                            <div class="progress" style="height:8px;border-radius:99px">
                                <div class="progress-bar" style="width:{{ $efek }}%;background:{{ $efekColor }};border-radius:99px"></div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:0.75rem">
                                {{ $row['peserta_selesai'] }} dari {{ $row['peserta_mengikuti'] }} peserta menyelesaikan
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div class="p-2 rounded-2 mb-1" style="background:#f0f9ff;font-size:0.78rem;color:#0369a1">
                            💡 {{ $row['pre_test_note'] }}
                        </div>
                        <div class="p-2 rounded-2" style="background:#f0fdf4;font-size:0.78rem;color:#166534">
                            ✅ {{ $row['post_test_note'] }}
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ─── CHART ─── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="mb-0 fw-bold">📊 Grafik Perbandingan Nilai</h6>
    </div>
    <div class="card-body p-4">
        <canvas id="performanceChart" height="110"></canvas>
    </div>
</div>

@else

{{-- Empty state --}}
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <div class="fs-1 mb-2">📭</div>
        <h6 class="fw-semibold text-dark mb-1">Belum ada data performa</h6>
        <p class="text-muted small">Data akan muncul setelah peserta menyelesaikan pelatihan beserta pre & post test-nya.</p>
    </div>
</div>

@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@if(count($result) > 0)
<script>
    new Chart(document.getElementById('performanceChart'), {
        type: 'bar',
        data: {
            labels: @json($result->pluck('course_title')),
            datasets: [
                {
                    label: 'Pre Test',
                    data: @json($result->pluck('avg_pre_test')),
                    backgroundColor: 'rgba(245,158,11,0.75)',
                    borderRadius: 5,
                    borderSkipped: false,
                },
                {
                    label: 'Post Test',
                    data: @json($result->pluck('avg_post_test')),
                    backgroundColor: 'rgba(16,185,129,0.75)',
                    borderRadius: 5,
                    borderSkipped: false,
                },
                {
                    label: 'Margin',
                    data: @json($result->pluck('avg_margin')),
                    backgroundColor: 'rgba(59,130,246,0.75)',
                    borderRadius: 5,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 18 }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y}`
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { stepSize: 20 },
                    grid: { color: 'rgba(0,0,0,.05)' }
                }
            }
        }
    });
</script>
@endif
@endpush
