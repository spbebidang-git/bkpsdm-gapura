<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use App\Console\Commands\PostCertificateCommand;

class SsoSuccessCheckTest extends TestCase
{
    private function callIsSsoSuccess($status, $json, $body)
    {
        $fake = new class($status, $json, $body) {
            public function __construct(public $status, public $json, public $body) {}
            public function successful(): bool { return $this->status >= 200 && $this->status < 300; }
            public function status(): int { return $this->status; }
            public function json(): mixed { return $this->json; }
            public function body(): string { return $this->body; }
        };

        $cmd = new PostCertificateCommand();
        $m = new ReflectionMethod($cmd, 'isSsoSuccess');
        return $m->invoke($cmd, $fake);
    }

    public function test_http_200_body_error_message_dianggap_gagal(): void
    {
        // Kasus user: SSO balas 200 tapi data tidak masuk (body bilang error)
        $this->assertFalse($this->callIsSsoSuccess(200, ['message' => 'NIP tidak terdaftar'], ''));
    }

    public function test_http_200_body_ok_dianggap_sukses(): void
    {
        $this->assertTrue($this->callIsSsoSuccess(200, ['message' => 'Data berhasil disimpan'], ''));
        $this->assertTrue($this->callIsSsoSuccess(200, null, '{"status":"ok"}'));
    }

    public function test_http_500_dianggap_gagal(): void
    {
        $this->assertFalse($this->callIsSsoSuccess(500, null, 'Internal Server Error'));
    }

    public function test_http_200_error_field_truthy_dianggap_gagal(): void
    {
        $this->assertFalse($this->callIsSsoSuccess(200, ['error' => 'invalid nip'], ''));
    }

    public function test_http_200_body_kosong_dianggap_sukses(): void
    {
        // Body kosong = tidak ada info gagal → 2xx menang (jangan break flow lama)
        $this->assertTrue($this->callIsSsoSuccess(200, null, ''));
    }
}
