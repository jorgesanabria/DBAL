<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class DevelopmentErrorMiddleware implements MiddlewareInterface
{
    private $toStderr;
    private $path;

    public function __construct(bool $toStderr = false, string $path = null)
    {
        $this->toStderr = $toStderr;
        $this->path = rtrim($path ?: __DIR__ . '/../errors', '/');
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0777, true);
        }
        set_exception_handler([$this, 'handleException']);
    }

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    public function handleException(\Throwable $e): void
    {
        $html = $this->renderHtml($e);
        if (PHP_SAPI === 'cli') {
            $text = $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
            if ($this->toStderr) {
                file_put_contents('php://stderr', $text);
            } else {
                echo $text;
            }
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
        }
        $this->persist($html);
        exit(1);
    }

    private function renderHtml(\Throwable $e): string
    {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        return "<!DOCTYPE html>
<html><head><meta charset='utf-8'><title>Application error</title>
<link rel='stylesheet' href='style.css'>
</head><body class='light font-medium'>
<h1>An error occurred</h1>
<p class='message'>{$message}</p>
<pre class='trace'>{$trace}</pre>
<div class='controls'>Theme:
 <button onclick=\"setTheme('light')\">Light</button>
 <button onclick=\"setTheme('dark')\">Dark</button>
 Font size:
 <button onclick=\"setFont('small')\">Small</button>
 <button onclick=\"setFont('medium')\">Medium</button>
 <button onclick=\"setFont('large')\">Large</button>
</div>
<script src='script.js'></script>
</body></html>";
    }

    private function css(): string
    {
        return "body{font-family:Arial,sans-serif;margin:20px;}body.light{background:#fff;color:#000;}body.dark{background:#000;color:#fff}.font-small{font-size:14px}.font-medium{font-size:18px}.font-large{font-size:22px}pre{white-space:pre-wrap}";
    }

    private function js(): string
    {
        return "function setTheme(t){document.body.classList.remove('light','dark');document.body.classList.add(t)}function setFont(s){document.body.classList.remove('font-small','font-medium','font-large');document.body.classList.add('font-'+s)}";
    }

    private function persist(string $html): void
    {
        $dir = $this->path . '/' . date('Ymd_His');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . '/error.html', $html);
        file_put_contents($dir . '/style.css', $this->css());
        file_put_contents($dir . '/script.js', $this->js());
    }
}
