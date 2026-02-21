@extends('admin.layouts.app')

@section('breadcrumb', 'Detail Kursus')

@section('content')

    {{-- Info Kursus --}}
    <div class="card p-0 mb-4">
        <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
            <span>Informasi Kursus</span>
            <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">← Kembali</a>
        </h5>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center mb-3">
                    <img src="{{ $course->thumbnail ? asset($course->thumbnail) : asset('images/no-image.png') }}"
                         alt="Thumbnail" class="img-thumbnail rounded" style="max-height: 180px; object-fit: cover;">
                </div>
                <div class="col-md-9">
                    <table class="table table-bordered align-middle">
                        <tr>
                            <td style="width:160px;white-space:nowrap" class="text-muted">Judul</td>
                            <th>{{ $course->title ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td class="text-muted">Kategori</td>
                            <th>{{ $course->category->title ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td class="text-muted">Instruktur</td>
                            <th>{{ $course->instructor->name ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td class="text-muted">Tipe</td>
                            <th>
                                <span class="badge {{ $course->type == 'schedule' ? 'bg-primary' : 'bg-success' }}">
                                    {{ ucfirst($course->type) }}
                                </span>
                            </th>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <th>
                                <span class="badge {{ $course->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($course->status ?? '-') }}
                                </span>
                            </th>
                        </tr>
                        <tr>
                            <td class="text-muted">Jam Pelajaran</td>
                            <th>{{ $course->jam_pelajaran ?? '-' }}</th>
                        </tr>
                        @if($course->type == 'unschedule')
                        <tr>
                            <td class="text-muted">Berlaku Hingga</td>
                            <th>{{ $course->expired ? \Carbon\Carbon::parse($course->expired)->format('d/m/Y') : '-' }}</th>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Total Peserta</td>
                            <th>{{ $course->total_participant ?? 0 }} orang</th>
                        </tr>
                        <tr>
                            <td class="text-muted">Peserta Selesai</td>
                            <th>{{ $course->total_passed ?? 0 }} orang</th>
                        </tr>
                        <tr>
                            <td class="text-muted">Efektivitas</td>
                            <th>{{ $course->efektifitas ?? 0 }}%</th>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Deskripsi Singkat --}}
            <div class="mt-2">
                <div class="text-muted small mb-1 fw-bold">Deskripsi Singkat</div>
                <p class="text-sm text-gray-600">{{ $course->short_description ?? '-' }}</p>
            </div>

            {{-- Deskripsi Lengkap --}}
            <div class="mt-2">
                <div class="text-muted small mb-1 fw-bold">Deskripsi Lengkap</div>
                <div class="border rounded p-3 bg-light small" style="max-height:200px;overflow-y:auto">
                    {!! $course->description ?? '-' !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Jadwal (jika type = schedule) --}}
    @if ($course->type == 'schedule')
        <div class="card p-0 mb-4">
            <h5 class="card-header border-bottom">Jadwal Kursus</h5>
            <div class="card-body p-0">
                <div class="table-responsive p-4">
                    <table class="table table-bordered text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedules as $i => $schedule)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($schedule->date)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($schedule->time_from)->format('H:i') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($schedule->time_to)->format('H:i') }}</td>
                                    <td>{{ $schedule->quota ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">Belum ada jadwal</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Peserta --}}
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card p-0 h-100">
                <h5 class="card-header border-bottom d-flex justify-content-between">
                    <span>Peserta Selesai</span>
                    <span class="badge bg-success">{{ count($finishedParticipant) }}</span>
                </h5>
                <div class="card-body p-0">
                    <div class="table-responsive p-3">
                        <table class="table table-sm table-bordered text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($finishedParticipant as $i => $participant)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="text-start">{{ $participant->name }}</td>
                                        <td>{{ $participant->job_title ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">Belum ada peserta selesai</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card p-0 h-100">
                <h5 class="card-header border-bottom d-flex justify-content-between">
                    <span>Peserta Sedang Belajar</span>
                    <span class="badge bg-warning text-dark">{{ count($onGoingParticipant) }}</span>
                </h5>
                <div class="card-body p-0">
                    <div class="table-responsive p-3">
                        <table class="table table-sm table-bordered text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($onGoingParticipant as $i => $participant)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="text-start">{{ $participant->name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">Tidak ada peserta sedang belajar</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
