@extends('app')

@section('title', 'Laporan')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Laporan Peserta</h1>
    @php
    $query = \App\Models\Learning\Learn::with('participant', 'learnItems')->get();
    $a = $query->map(function ($row) {
        $pre = 0;
        $preCorrect = 0;
        $preWrong = 0;
        $preScore = 0;
        if ($row->learnItems->where('type', 'pre-test')->first()) {
            $responsePre = unserialize($row->learnItems->where('type', 'pre-test')->first()->response);
            foreach ($responsePre as $item) {
                $pre += $item['score'];
            }
            $preCorrect = count(array_filter($responsePre, fn($item) => $item['is_correct'] === true));
            $preWrong = count(array_filter($responsePre, fn($item) => $item['is_correct'] === false));
            $preScore = $responsePre[0]['score'] ?? 0;
        }

        $post = 0;
        $postCorrect = 0;
        $postWrong = 0;
        $postScore = 0;
        if ($row->learnItems->where('type', 'post-test')->first()) {
            $responsePost = unserialize($row->learnItems->where('type', 'post-test')->first()->response);
            foreach ($responsePost as $item) {
                $post += $item['score'];
            }
            $postCorrect = count(array_filter($responsePost, fn($item) => $item['is_correct'] === true));
            $postWrong = count(array_filter($responsePost, fn($item) => $item['is_correct'] === false));
            $postScore = $responsePost[0]['score'] ?? 0;
        }

        return [
            'name' => $row->participant->name,
            'pre_score_total' => $pre,
            'pre_correct' => $preCorrect,
            'pre_wrong' => $preWrong,
            'pre_score' => $preScore,
            'post_score_total' => $post,
            'post_correct' => $postCorrect,
            'post_wrong' => $postWrong,
            'post_score' => $postScore,
            'finished_at' => $row->finished_at,
        ];
    });
    @endphp

    <table class="min-w-full border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="border border-gray-300 px-4 py-2">Nama</th>
                <th class="border border-gray-300 px-4 py-2">Pre Total</th>
                <th class="border border-gray-300 px-4 py-2">Pre Benar</th>
                <th class="border border-gray-300 px-4 py-2">Pre Salah</th>
                <th class="border border-gray-300 px-4 py-2">Pre Score</th>
                <th class="border border-gray-300 px-4 py-2">Post Total</th>
                <th class="border border-gray-300 px-4 py-2">Post Benar</th>
                <th class="border border-gray-300 px-4 py-2">Post Salah</th>
                <th class="border border-gray-300 px-4 py-2">Post Score</th>
                <th class="border border-gray-300 px-4 py-2">Selesai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($a as $item)
                <tr>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['name'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['pre_score_total'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['pre_correct'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['pre_wrong'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['pre_score'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['post_score_total'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['post_correct'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['post_wrong'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['post_score'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['finished_at'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
