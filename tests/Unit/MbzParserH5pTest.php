<?php

namespace Tests\Unit;

use App\Services\Course\Import\MbzParser;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MbzParserH5pTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'mbz_h5p_test_' . bin2hex(random_bytes(6));
        mkdir($this->workDir . DIRECTORY_SEPARATOR . 'activities'
            . DIRECTORY_SEPARATOR . 'h5pactivity_1', 0775, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->workDir)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->workDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->workDir);
        }

        parent::tearDown();
    }

    public function test_h5p_activity_is_supported_by_the_moodle_importer(): void
    {
        $this->assertSame('h5p', MbzParser::TYPE_MAP['h5pactivity']);
    }

    public function test_h5p_resolver_selects_the_package_instead_of_other_referenced_files(): void
    {
        $activityDir = 'activities/h5pactivity_1';
        file_put_contents(
            $this->workDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $activityDir)
                . DIRECTORY_SEPARATOR . 'inforef.xml',
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<inforef>
  <fileref>
    <file><id>1</id></file>
    <file><id>2</id></file>
  </fileref>
</inforef>
XML
        );

        $files = [
            '1' => [
                'contenthash' => 'image-hash',
                'filename' => 'preview.png',
                'mimetype' => 'image/png',
                'filesize' => 999999,
            ],
            '2' => [
                'contenthash' => 'package-hash',
                'filename' => 'module.h5p',
                'mimetype' => 'application/zip.h5p',
                'filesize' => 100,
            ],
        ];

        $method = new ReflectionMethod(MbzParser::class, 'resolveActivityFile');
        $result = $method->invoke(
            new MbzParser(),
            $this->workDir,
            $activityDir,
            $files,
            'h5p'
        );

        $this->assertSame('package-hash', $result['contenthash']);
        $this->assertSame('module.h5p', $result['filename']);
    }

    public function test_moodle_video_file_is_detected_from_mime_type_or_extension(): void
    {
        $method = new ReflectionMethod(MbzParser::class, 'isVideoFile');
        $parser = new MbzParser();

        $this->assertTrue($method->invoke($parser, [
            'filename' => 'video-tanpa-ekstensi',
            'mimetype' => 'video/mp4',
        ]));
        $this->assertTrue($method->invoke($parser, [
            'filename' => 'pengantar.webm',
            'mimetype' => 'application/octet-stream',
        ]));
        $this->assertFalse($method->invoke($parser, [
            'filename' => 'materi.pdf',
            'mimetype' => 'application/pdf',
        ]));
    }
}
