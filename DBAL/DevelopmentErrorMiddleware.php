<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz DevelopmentErrorMiddleware
 */
class DevelopmentErrorMiddleware implements MiddlewareInterface
{
    private bool $console;
    private ?string $persistPath;
    private string $theme;
    private string $fontSize;

/**
 * __construct
 * @param array $options
 * @return void
 */

    public function __construct(array $options = [])
    {
        $this->console = isset($options['console']) ? (bool)$options['console'] : false;
        $this->persistPath = isset($options['persistPath']) ? rtrim((string)$options['persistPath'], '/') : null;

        $theme = isset($options['theme']) ? $options['theme'] : 'light';
        $this->theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';

        $fontSize = isset($options['fontSize']) ? $options['fontSize'] : 'medium';
        $this->fontSize = in_array($fontSize, ['small', 'medium', 'large'], true) ? $fontSize : 'medium';

        if ($this->persistPath !== null && !is_dir($this->persistPath)) {
            @mkdir($this->persistPath, 0777, true);
        }
        set_exception_handler([$this, 'handleException']);
    }

/**
 * __invoke
 * @param MessageInterface $msg
 * @return void
 */

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

/**
 * handleException
 * @param \Throwable $e
 * @return void
 */

    public function handleException(\Throwable $e): void
    {
        $html = $this->renderHtml($e);

        if ($this->console) {
            $text = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
            file_put_contents('php://stderr', $text);
        } elseif (PHP_SAPI !== 'cli') {
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
        } else {
            echo $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        }

        $this->persist($html);
        exit(1);
    }

/**
 * renderHtml
 * @param \Throwable $e
 * @return string
 */

    private function renderHtml(\Throwable $e): string
    {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line = (int)$e->getLine();
        $snippet = '';
        if (is_file($e->getFile())) {
            $lines = @file($e->getFile());
            $start = max(0, $line - 3);
            $slice = array_slice($lines, $start, 6, true);
            foreach ($slice as $num => $code) {
                $num++;
                $code = htmlspecialchars(rtrim($code), ENT_QUOTES, 'UTF-8');
                $indicator = $num === $line ? '>> ' : '   ';
                $snippet .= $indicator . str_pad((string)$num, 4, ' ', STR_PAD_LEFT) . " | " . $code . "\n";
            }
        }

        return "<!DOCTYPE html>
<html><head><meta charset='utf-8'><title>Application error</title>
<link rel='stylesheet' href='style.css'>
</head><body class='{$this->theme} font-{$this->fontSize}'>
<h1>An error occurred</h1>
<p class='message'>{$message}</p>
<p class='location'>{$file}:{$line}</p>
<pre class='code'>{$snippet}</pre>
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

/**
 * css
 * @return string
 */

    private function css(): string
    {
        return "body{font-family:Arial,sans-serif;margin:20px;}body.light{background:#fff;color:#000;}body.dark{background:#000;color:#fff}.font-small{font-size:14px}.font-medium{font-size:18px}.font-large{font-size:22px}pre{white-space:pre-wrap}.location{margin-bottom:10px;font-style:italic}.code{background:#f5f5f5;padding:10px}";
    }

/**
 * js
 * @return string
 */

    private function js(): string
    {
        return "function setTheme(t){document.body.classList.remove('light','dark');document.body.classList.add(t)}function setFont(s){document.body.classList.remove('font-small','font-medium','font-large');document.body.classList.add('font-'+s)}";
    }

/**
 * persist
 * @param string $html
 * @return void
 */

    private function persist(string $html): void
    {
        if ($this->persistPath === null) {
            return;
        }

        $dir = $this->persistPath . '/' . date('Ymd_His');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . '/error.html', $html);
        file_put_contents($dir . '/style.css', $this->css());
        file_put_contents($dir . '/script.js', $this->js());
    }
}
