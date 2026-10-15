<?php

return [
    'completed' => [
        'label' => 'Pelatihan selesai',
        'group' => 'Pelatihan',
        'type' => 'completed',
        'title' => 'Pelatihan selesai',
        'message' => 'Selamat, Anda telah menyelesaikan :course_name.',
    ],
    'certificate' => [
        'label' => 'Sertifikat tersedia',
        'group' => 'Sertifikat',
        'type' => 'certificate',
        'title' => 'Sertifikat tersedia',
        'message' => 'Sertifikat :course_name sudah tersedia.',
    ],
    'reminder_24h' => [
        'label' => 'Pengingat 24 jam',
        'group' => 'Masa akses kelas',
        'type' => 'deadline',
        'title' => 'Tenggat kelas mendekat',
        'message' => 'Akses :course_name berakhir dalam 24 jam.',
    ],
    'reminder_1h' => [
        'label' => 'Pengingat 1 jam',
        'group' => 'Masa akses kelas',
        'type' => 'deadline',
        'title' => 'Tenggat kelas mendekat',
        'message' => 'Akses :course_name berakhir dalam 1 jam.',
    ],
    'expired' => [
        'label' => 'Masa akses berakhir',
        'group' => 'Masa akses kelas',
        'type' => 'expired',
        'title' => 'Tenggat kelas terlewati',
        'message' => 'Masa akses :course_name telah berakhir.',
    ],
    'grace' => [
        'label' => 'Tambahan waktu akses',
        'group' => 'Masa akses kelas',
        'type' => 'grace',
        'title' => 'Masa transisi akses kelas',
        'message' => 'Anda mendapat waktu tambahan untuk menyelesaikan :course_name.',
    ],
];
