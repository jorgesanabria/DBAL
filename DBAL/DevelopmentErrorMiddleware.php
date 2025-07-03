<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class DevelopmentErrorMiddleware implements MiddlewareInterface
{
    private $dir;
    private $console;
    private $stream;

    public function __construct(string $dir, bool $console = false, $stream = null)
    {
        $this->dir = $dir;
        $this->console = $console;
        $this->stream = $stream ?: fopen('php://stderr', 'w');
    }

    public function __invoke(MessageInterface $message): void
    {
        // no-op
    }

    public function register(): void
    {
        set_exception_handler([$this, 'handle']);
    }

    public function handle(\Throwable $e): void
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
        $file = $this->dir . '/' . uniqid('exception_', true) . '.html';
        $body = '<html><body><pre>' . htmlspecialchars((string) $e) . '</pre></body></html>';
        file_put_contents($file, $body);
        if ($this->console) {
            fwrite($this->stream, (string) $e);
        }
    }
}
