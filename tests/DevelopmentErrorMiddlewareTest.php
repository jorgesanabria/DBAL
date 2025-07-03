<?php
use PHPUnit\Framework\TestCase;
use DBAL\DevelopmentErrorMiddleware;

class DevelopmentErrorMiddlewareTest extends TestCase
{
    public function testHtmlFileAndConsoleOutput()
    {
        $dir = sys_get_temp_dir() . '/deverr_' . uniqid();
        mkdir($dir);
        $stream = fopen('php://memory', 'w+');
        $mw = new DevelopmentErrorMiddleware($dir, true, $stream);
        $mw->handle(new Exception('boom'));
        $files = glob($dir . '/*.html');
        $this->assertCount(1, $files);
        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('boom', $content);
        rewind($stream);
        $output = stream_get_contents($stream);
        $this->assertStringContainsString('boom', $output);
        array_map('unlink', $files);
        rmdir($dir);
    }
}
