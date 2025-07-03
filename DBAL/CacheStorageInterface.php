<?php
namespace DBAL;

interface CacheStorageInterface
{
    public function get(string $key);
    public function set(string $key, $value): void;
    /**
     * Delete a cached value. If $key is null, all entries MUST be removed.
     */
    public function delete(string $key = null): void;
}
