<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\DevelopmentErrorMiddleware;

class DevelopmentErrorMiddlewareTest extends TestCase
{
    public function testRenderAndPersist(): void
    {
        $dir = sys_get_temp_dir() . '/deverr_' . uniqid();
        $mw = new DevelopmentErrorMiddleware([
            'console' => false,
            'persistPath' => $dir,
            'theme' => 'dark',
            'fontSize' => 'large',
        ]);
        // restore previous exception handler
        restore_exception_handler();

        $ref = new ReflectionClass($mw);
        $render = $ref->getMethod('renderHtml');
        $render->setAccessible(true);
        $html = $render->invoke($mw, new Exception('boom'));
        $this->assertStringContainsString('boom', $html);

        $persist = $ref->getMethod('persist');
        $persist->setAccessible(true);
        $persist->invoke($mw, $html);

        $dirs = glob($dir . '/*');
        $this->assertNotEmpty($dirs);
        $files = scandir($dirs[0]);
        $this->assertContains('error.html', $files);

        $css = $ref->getMethod('css');
        $css->setAccessible(true);
        $this->assertStringContainsString('body', $css->invoke($mw));

        $js = $ref->getMethod('js');
        $js->setAccessible(true);
        $this->assertStringContainsString('setTheme', $js->invoke($mw));

        foreach ($dirs as $d) { array_map('unlink', glob($d.'/*')); rmdir($d); }
        rmdir($dir);
    }
}
