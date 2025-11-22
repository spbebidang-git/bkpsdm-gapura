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

    <!-- Performa Kursus -->
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Performa Margin Kursus
        </h5>

        <div class="card-body row p-4">

            <!-- Tabel -->
            <div class="col-5">
                <div class="table-responsive mb-4">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>Materi</th>
                                <th class="text-nowrap" style="width: 50px;">Margin Performa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result as $row)
                                <tr>
                                    <td>{{ $row['course_title'] }}</td>
                                    <td>{{ $row['avg_margin'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chart -->
            <div class="col-7">
                <canvas id="performanceChart" class="border p-3" height="250"></canvas>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
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


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('performanceChart').getContext('2d');

        const chartData = {
            labels: @json($result->pluck('course_title')),
            datasets: [{
                label: 'Margin Performa',
                data: @json($result->pluck('avg_margin')),
                backgroundColor: '#2563eb',
                borderRadius: 6,
            }]
        };

        new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'Grafik Margin Performa Kursus',
                        font: {
                            size: 18
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 10
                        }
                    }
                }
            }
        });
    </script>
@endpush
