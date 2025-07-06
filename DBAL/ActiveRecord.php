<?php
declare(strict_types=1);
namespace DBAL;

/**
 * Lightweight Active Record wrapper backed by a Crud instance.
 */
class ActiveRecord implements \JsonSerializable
{
    private array $original = [];
    private array $modified = [];

/**
 * Create a new ActiveRecord bound to the given Crud instance.
 *
 * @param Crud  $crud      Crud object used for persistence
 * @param array $original  Original field values
 */

    public function __construct(private Crud $crud, array $original)
    {
        $this->original = $original;
    }

/**
 * Handle dynamic getters and setters for record fields.
 *
 * @param string $name
 * @param array  $arguments
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
 * Magic getter to retrieve modified or original field values.
 *
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
 * Magic setter that marks the given field as modified.
 *
 * @param string $name
 * @param mixed  $value
 */

    public function __set(string $name, mixed $value): void
    {
        $this->modified[$name] = $value;
    }

/**
 * Persist all modified fields back to the database.
 *
 * @return int Number of affected rows
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
 * Convert the record into an array for JSON encoding.
 *
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
