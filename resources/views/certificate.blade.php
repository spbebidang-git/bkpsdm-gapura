<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Pelatihan | {{ $nama_peserta }} | {{ $sub_title }}</title>
    {{-- <link href="{{ public_path('assets/css/tailwind-pdf.css') }}" rel="stylesheet"> --}}

    <style>
        @page {
            size: A4 landscape;
            /* Ukuran kertas A4 */
            margin: 0;
        }

        @font-face {
            font-family: 'Causten';
            src: url('{{ storage_path('fonts/Causten-Regular.ttf') }}') format('truetype');
            font-weight: normal;
        }

        @font-face {
            font-family: 'Causten';
            src: url('{{ storage_path('fonts/Causten-Bold.ttf') }}') format('truetype');
            font-weight: 900;
        }

        @font-face {
            font-family: 'Causten';
            src: url('{{ storage_path('fonts/Causten-Medium.ttf') }}') format('truetype');
            font-weight: 500;
        }

        body {
            font-family: 'Causten', sans-serif;
            line-height: 14px;
        }

        body {
            /* font-family: Arial, sans-serif;
            text-align: center; */
            width: 100%;
            height: 100%;
            margin: 0;
            position: relative;
        }

        .certificate {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('{{ public_path('assets\media\images\static\certificate_template.png') }}') no-repeat center center;
            background-size: cover;
        }
    </style>
</head>

<body>
    <div class="certificate">
        <div class="background">
            <div class="text-center"
                style="position: absolute; top: 240px; left: 147px; width: 537px; color: #003954; font-weight: 500; text-align: center; font-size: 20px">
                No.
                Sert: {{ $no_urut }} / GAPURA / 437.73 /2024
            </div>
            <div
                style="position: absolute; top: 295px; left: 147px; width: 537px; color: black; font-weight: 900; text-align: center; font-size: 24px">
                DIBERIKAN KEPADA:</div>
            <div
                style="position: absolute; top: 353px; left: 60px; width: 700px; color: #cd930f; font-weight: 900; text-align: center; font-size: 44px">
                {{ $nama_peserta }}</div>
            <div
                style="position: absolute; top: 380px; left: 147px; width: 537px; color: black; font-weight: 500; text-align: center; font-size: 18px">
                {{ $nip }} ({{ $pangkat }})</div>
            <div
                style="position: absolute; top: 399px; left: 147px; width: 537px; color: black; font-weight: 500; text-align: center; font-size: 18px">
                {{ $jabatan }} pada {{ $departemen }}</div>
            <div
                style="position: absolute; top: 435px; left: 147px; width: 537px; color: #003954; font-weight: 900; text-align: center; font-size: 20px">
                Telah mengikuti</div>
            <div
                style="position: absolute; text-transform: uppercase;  top: 470px; left: 147px; width: 537px; color: #003954; font-weight: 900; text-align: center; font-size: 26px">
                {{ $title }}</div>
            <div
                style="position: absolute; top: 491px; text-transform: uppercase; left: 147px; width: 537px; color: #003954; font-weight: 900; text-align: center; font-size: 22px">
                {{ $sub_title }}</div>
            <div
                style="position: absolute; top: 515px; left: 147px; width: 537px; color: #003954; font-weight: 500; text-align: center; font-size: 18px">
                yang diselenggarakan oleh BKPSDM Kabupaten Gresik</div>
            <div
                style="position: absolute; top: 535px; left: 147px; width: 537px; color: #003954; font-weight: 500; text-align: center; font-size: 18px">
                pada tanggal <b>{{ $date_finish }}</b> selama <b>{{ $sum_value }} Jam Pelajaran</b></div>
            <div
                style="position: absolute; top: 615px; left: 279px; width: 537px; color: black; font-weight: 900; font-size: 18px">
                Gresik, {{ $date_finish }}</div>
            <div
                style="position: absolute; top: 642px; left: 279px; width: 537px; color: black; font-weight: 500; font-size: 16px">
                Ditandatangani secara elektronik
                oleh
            </div>
            <div
                style="position: absolute; top: 662px; left: 279px; width: 537px; color: black; font-weight: 500; font-size: 16px">
                Kepala Badan Kepegawaian dan
            </div>
            <div
                style="position: absolute; top: 682px; left: 279px; width: 537px; color: black; font-weight: 500; font-size: 16px">
                Pengembangan Sumber Daya Manusia
            </div>
            <div
                style="position: absolute; top: 702px; left: 279px; width: 537px; color: black; font-weight: 500; font-size: 16px">
                Kabupaten Gresik</div>
            <div
                style="position: absolute; top: 726px; left: 279px; width: 537px; color: black; font-weight: 900; font-size: 18px">
                AGUNG ENDRO D.S UTOMO S.STP.,
                M.Si.</div>
            <div
                style="position: absolute; top: 746px; left: 279px; width: 537px; color: black; font-weight: 500; font-size: 16px">
                Pembina Utama Muda</div>
            <div
                style="position: absolute; top: 766px; left: 279px; width: 537px; color: black; font-weight: 500; font-size: 16px">
                NIP 197603051995111001</div>
        </div>
    </div>
</body>

</html>
