<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\LoggingMiddleware;
use Psr\Log\LoggerInterface;

class LoggingMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        return $pdo;
    }

    public function testLoggerInterfaceIsUsed()
    {
        $pdo = $this->createPdo();
        $logger = new class implements LoggerInterface {
            public array $records = [];
            public function emergency($m, array $c = []) { $this->log('emergency', $m, $c); }
            public function alert($m, array $c = [])    { $this->log('alert', $m, $c); }
            public function critical($m, array $c = []) { $this->log('critical', $m, $c); }
            public function error($m, array $c = [])    { $this->log('error', $m, $c); }
            public function warning($m, array $c = [])  { $this->log('warning', $m, $c); }
            public function notice($m, array $c = [])   { $this->log('notice', $m, $c); }
            public function info($m, array $c = [])     { $this->log('info', $m, $c); }
            public function debug($m, array $c = [])    { $this->log('debug', $m, $c); }
            public function log($level, $message, array $context = []) { $this->records[] = [$level, $message, $context]; }
        };

        $mw = new LoggingMiddleware($logger);
        $crud = (new Crud($pdo))->from('test')->withMiddleware($mw);

        $crud->insert(['name' => 'A']);

        $this->assertNotEmpty($logger->records);
        $this->assertStringContainsString('INSERT INTO', $logger->records[0][1]);
    }

    public function testCallableLoggerIsUsed()
    {
        $pdo = $this->createPdo();
        $log = [];
        $callable = function ($sql, $values) use (&$log) {
            $log[] = [$sql, $values];
        };

        $mw = new LoggingMiddleware($callable);
        $crud = (new Crud($pdo))->from('test')->withMiddleware($mw);

        $crud->insert(['name' => 'A']);

        $this->assertCount(1, $log);
        $this->assertStringContainsString('INSERT INTO', $log[0][0]);
    }
}
