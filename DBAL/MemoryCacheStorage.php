<?php
namespace DBAL;

class MemoryCacheStorage implements CacheStorageInterface
{
    private $data = [];

    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function delete(string $key = null): void
    {
        if ($key === null) {
            $this->data = [];
        } else {
            unset($this->data[$key]);
        }
    }
}
