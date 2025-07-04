<?php
declare(strict_types=1);
namespace DBAL;

use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Clase/Interfaz LazyRelation
 */
class LazyRelation implements IteratorAggregate, JsonSerializable
{
    private bool $loaded = false;
    private mixed $data;

/**
 * __construct
 * @param callable $loader
 * @return void
 */

    public function __construct(private $loader)
    {
    }

/**
 * load
 * @return void
 */

    private function load(): void
    {
        if (!$this->loaded) {
            $this->data = ($this->loader)();
            $this->loaded = true;
        }
    }

/**
 * get
 * @return mixed
 */

    public function get()
    {
        $this->load();
        return $this->data;
    }

/**
 * __invoke
 * @return mixed
 */

    public function __invoke()
    {
        return $this->get();
    }

/**
 * getIterator
 * @return mixed
 */

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

/**
 * jsonSerialize
 * @return mixed
 */

    public function jsonSerialize()
    {
        $this->load();
        return $this->data;
    }
}
