<?php
namespace DBAL;

/**
 * Clase/Interfaz ActiveRecord
 */
class ActiveRecord implements \JsonSerializable
{
/** @var mixed */
        private $crud;
/** @var mixed */
        private $original = [];
/** @var mixed */
        private $modified = [];

/**
 * __construct
 * @param Crud $crud
 * @param array $data
 * @return void
 */

        public function __construct(Crud $crud, array $data)
        {
                $this->crud = $crud;
                $this->original = $data;
        }

/**
 * __call
 * @param mixed $name
 * @param mixed $arguments
 * @return mixed
 */

        public function __call($name, $arguments)
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
 * @param mixed $name
 * @return mixed
 */

        public function __get($name)
        {
                return array_key_exists($name, $this->modified)
                        ? $this->modified[$name]
                        : ($this->original[$name] ?? null);
        }

/**
 * __set
 * @param mixed $name
 * @param mixed $value
 * @return mixed
 */

        public function __set($name, $value)
        {
                $this->modified[$name] = $value;
        }

/**
 * update
 * @return mixed
 */

        public function update()
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
 * @return mixed
 */

        public function jsonSerialize()
        {
                $data = $this->original;
                foreach ($this->modified as $k => $v) {
                        $data[$k] = $v;
                }
                return $data;
        }
}
