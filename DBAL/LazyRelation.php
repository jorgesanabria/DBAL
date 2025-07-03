<?php
namespace DBAL;

use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

class LazyRelation implements IteratorAggregate, JsonSerializable
{
    private $loader;
    private $loaded = false;
    private $data;

    public function __construct(callable $loader)
    {
        $this->loader = $loader;
    }

    private function load(): void
    {
        if (!$this->loaded) {
            $this->data = ($this->loader)();
            $this->loaded = true;
        }
    }

    public function get()
    {
        $this->load();
        return $this->data;
    }

    public function __invoke()
    {
        return $this->get();
    }

    public function getIterator()
    {
        $this->load();
        if ($this->data instanceof \Traversable) {
            return $this->data;
        }
        if (is_array($this->data)) {
            return new ArrayIterator($this->data);
        }
        return new ArrayIterator($this->data === null ? [] : [$this->data]);
    }

    public function jsonSerialize()
    {
        $this->load();
        return $this->data;
    }
}
