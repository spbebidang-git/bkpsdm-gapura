<?php

namespace Tests\Unit;

use App\Repositories\Identity\Contracts\OpdRepositoryInterface;
use App\Services\Integration\OpdSyncService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class OpdSyncServiceTest extends TestCase
{
    public function test_structure_sync_extracts_only_unique_top_level_ukers(): void
    {
        config(['services.kedaton.base_url' => 'https://kedaton.test', 'services.kedaton.data_key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['kedaton.test/*' => Http::response(['message' => 'BERHASIL', 'data' => [
            ['kode_uker' => '001', 'uker' => 'Dinas Pendidikan', 'bidang' => [['kode_satker' => '002', 'satker' => 'Bidang Pendidikan']]],
            ['kode_uker' => '002', 'nama_uker' => 'Dinas Kesehatan', 'nama_kepala' => 'Nama Kepala'],
            ['kode_uker' => '001', 'uker' => 'Dinas Pendidikan'],
            ['kode_satker' => '003', 'satker' => 'Bidang Lain'],
        ]])]);
        $repo = Mockery::mock(OpdRepositoryInterface::class);
        foreach (['001' => 'Dinas Pendidikan', '002' => 'Dinas Kesehatan'] as $code => $name) {
            $repo->shouldReceive('findByCodeIncludingTrashed')->once()->with($code)->andReturnNull();
            $repo->shouldReceive('create')->once()->with(Mockery::on(fn ($data) => $data['code'] === $code && $data['name'] === $name && $data['is_active'] === true))->andReturn(new \App\Models\Identity\Opd);
        }
        $repo->shouldReceive('deactivateMissingSynced')->once()->with(['001', '002'])->andReturn(0);
        $result = (new OpdSyncService($repo))->sync();
        $this->assertSame(2, $result['created']);
        Http::assertSent(fn ($request) => $request->url() === 'https://kedaton.test/api/get_data_api' && $request->method() === 'POST' && $request['key'] === 'test-key' && $request['tipe_data'] === 'perangkat' && array_key_exists('nip', $request->data()) && $request['nip'] === '' && array_key_exists('nik', $request->data()) && $request['nik'] === '' && array_key_exists('uker_kode', $request->data()) && $request['uker_kode'] === '');
    }

    public function test_failed_structure_response_does_not_change_opds(): void
    {
        config(['services.kedaton.base_url' => 'https://kedaton.test', 'services.kedaton.data_key' => 'test-key']);
        Http::fake(['kedaton.test/*' => Http::response(['message' => 'GAGAL', 'data' => []])]);
        $repo = Mockery::mock(OpdRepositoryInterface::class);
        $this->expectException(\RuntimeException::class);
        (new OpdSyncService($repo))->sync();
    }
}
