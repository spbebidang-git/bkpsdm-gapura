@extends('admin.layouts.app')

@section('breadcrumb', '')

@section('content')
    <div class="mb-4">
        <div class="card p-0">
            <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
                Detail Kursus
            </h5>
            <div class="card-body p-0 overflow-hidden">
                <div class="table-responsive text-wrap overflow-hidden p-4">
                    <table class="table table-bordered text-center align-middle">
                        <tr>
                            <td style="white-space: nowrap;">Kategori</td>
                            {{-- Mengganti name menjadi title sesuai instruksi --}}
                            <th>{{ $course->category->title ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Instruktur</td>
                            {{-- Mengganti name menjadi title sesuai instruksi --}}
                            <th>{{ $course->instructor->title ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Tipe</td>
                            <th>{{ ucfirst($course->type) ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Judul</td>
                            <th>{{ $course->title ?? '-' }}</th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Logika: Hanya tampil jika type adalah schedule --}}
    @if ($course->type == 'schedule')
        <div class="mb-4">
            <div class="card p-0">
                <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
                    Jadwal Kursus
                </h5>
                <div class="card-body p-0 overflow-hidden">
                    <div class="table-responsive text-wrap overflow-hidden p-4">
                        <table class="table table-bordered text-center align-middle">
                            <thead>
                                <tr class="table-light">
                                    <th>Tanggal</th>
                                    <th>Jam Mulai</th>
                                    <th>Jam Selesai</th>
                                    <th>Kuota</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($schedules as $schedule)
                                    <tr>
                                        {{-- Kolom 1: Tanggal (22/01/2026) --}}
                                        <td>{{ \Carbon\Carbon::parse($schedule->date)->format('d/m/Y') }}</td>

                                        {{-- Kolom 2: Jam Mulai (09:00) --}}
                                        <td>{{ \Carbon\Carbon::parse($schedule->time_from)->format('H:i') }}</td>

                                        {{-- Kolom 3: Jam Selesai (11:00) --}}
                                        <td>{{ \Carbon\Carbon::parse($schedule->time_to)->format('H:i') }}</td>

                                        {{-- Kolom 4: Kuota --}}
                                        <td>{{ $schedule->quota ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Data tidak ditemukan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-6">
            <div class="card p-0 h-100">
                <h5 class="card-header border-bottom">Peserta Selesai</h5>
                <div class="card-body p-4">
                    <table class="table table-sm table-bordered text-center">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Jabatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($finishedParticipant as $participant)
                                <tr>
                                    <td>{{ $participant->name }}</td>
                                    <td>{{ $participant->job_title ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-muted">Belum ada peserta selesai</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-6">
        </div>
    </div>
@endsection
