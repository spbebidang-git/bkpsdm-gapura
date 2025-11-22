@extends('admin.layouts.app')

@section('breadcrumb', 'Performa Materi')

@section('content')
    <div class="card p-0">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            Margin Performa Materi
        </h5>

        <div class="card-body row p-4">
            <div class="col-5">
                <div class="table-responsive mb-4">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>Materi</th>
                                <th style="width: 50px;" class="text-nowrap">Margin Performa</th>
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

            <div class="col-7">
                <canvas id="performanceChart" class="border p-3" height="250"></canvas>
            </div>
        </div>
    </div>
@endsection

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
                        text: 'Grafik Margin Performa Materi',
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
