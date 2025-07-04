<?php
declare(strict_types=1);
namespace DBAL;

/**
 * Clase/Interfaz ActiveRecord
 */
class ActiveRecord implements \JsonSerializable
{
    private array $original = [];
    private array $modified = [];

/**
 * __construct
 * @param Crud $crud
 * @param array $original
 * @return void
 */

    public function __construct(private Crud $crud, array $original)
    {
        $this->original = $original;
    }

/**
 * __call
 * @param string $name
 * @param array $arguments
 * @return mixed
 */

    public function __call(string $name, array $arguments): mixed
    {
        if (strpos($name, 'get__') === 0) {
            $field = substr($name, 5);
            return array_key_exists($field, $this->modified)
                ? $this->modified[$field]
                : ($this->original[$field] ?? null);
        }
        if (strpos($name, 'set__') === 0) {
            $field = substr($name, 5);
            $this->modified[$field] = $arguments[0] ?? null;
            return $this;
        }
        throw new \BadMethodCallException(sprintf('Method %s does not exist', $name));
    }

/**
 * __get
 * @param string $name
 * @return mixed|null
 */

    public function __get(string $name): mixed
    {
        return array_key_exists($name, $this->modified)
            ? $this->modified[$name]
            : ($this->original[$name] ?? null);
    }

/**
 * __set
 * @param string $name
 * @param mixed $value
 * @return void
 */

    public function __set(string $name, mixed $value): void
    {
        $this->modified[$name] = $value;
    }

/**
 * update
 * @return int
 */

    public function update(): int
    {
        if (!array_key_exists('id', $this->original)) {
            throw new \RuntimeException('id field missing');
        }

        $changed = [];
        foreach ($this->modified as $field => $value) {
            if (!array_key_exists($field, $this->original) || $this->original[$field] !== $value) {
                $changed[$field] = $value;
            }
        }

        if (empty($changed)) {
            return 0;
        }

        $count = $this->crud
            ->where(['id__eq' => $this->original['id']])
            ->update($changed);

        $this->original = array_merge($this->original, $changed);
        $this->modified = [];

        return $count;
    }

/**
 * jsonSerialize
 * @return array
 */

    public function jsonSerialize(): array
    {
        $data = $this->original;
        foreach ($this->modified as $k => $v) {
            $data[$k] = $v;
        }

        return $data;
    }
}
