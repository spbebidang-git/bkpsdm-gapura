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
                            <th>{{ $course->category->name ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Instruktur</td>
                            <th>{{ $course->instructor->name ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Tipe</td>
                            <th>{{ ucfirst($course->type) ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Judul</td>
                            <th>{{ $course->title ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Deskripsi Singkat</td>
                            <th>{{ $course->short_description ?? '-' }}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Deskripsi</td>
                            <th>{!! $course->description ?? '-' !!}</th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Thumbnail</td>
                            <th>
                                @if ($course->thumbnail)
                                    <a href="{{ asset('assets/media/images/courses/' . $course->thumbnail) }}"
                                        target="_blank" class="text-primary text-decoration-underline">Lihat Thumbnail</a>
                                @else
                                    <span class="text-muted">Tidak Ada Gambar</span>
                                @endif
                            </th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Video Teaser</td>
                            <th>
                                @if ($course->teaser_video)
                                    <a href="{{ asset('storage/' . $course->teaser_video) }}" target="_blank"
                                        class="text-primary text-decoration-underline">Lihat Video</a>
                                @else
                                    <span class="text-muted">Tidak Ada Video</span>
                                @endif
                            </th>
                        </tr>
                        <tr>
                            <td style="white-space: nowrap;">Status</td>
                            <th>
                                <span class="badge bg-{{ $course->status == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($course->status) }}
                                </span>
                            </th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="card p-0">
            <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
                Jadwal Kursus
            </h5>
            <div class="card-body p-0 overflow-hidden">
                <div class="table-responsive text-wrap overflow-hidden p-4">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->date ?? '-' }}</td>
                                    <td>{{ $schedule->time ?? '-' }}</td>
                                    <td>{{ $schedule->quota ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">Data tidak ditemukan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-6">
            <div class="card p-0">
                <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
                    Peserta Selesai
                </h5>
                <div class="card-body p-0 overflow-hidden">
                    <div class="table-responsive text-wrap overflow-hidden p-4">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($finishedParticipant as $participant)
                                    <tr>
                                        <td>{{ $participant->name ?? '-' }}</td>
                                        <td>{{ $participant->job_title ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">Data tidak ditemukan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card p-0">
                <h5 class="card-header border-bottom d-flex justify-content-between align-items-center">
                    Peserta Berlangsung
                </h5>
                <div class="card-body p-0 overflow-hidden">
                    <div class="table-responsive text-wrap overflow-hidden p-4">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($onGoingParticipant as $participant)
                                    <tr>
                                        <td>{{ $participant->name ?? '-' }}</td>
                                        <td>{{ $participant->job_title ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">Data tidak ditemukan</td>
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
