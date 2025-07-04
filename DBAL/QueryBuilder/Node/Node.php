<?php
namespace DBAL\QueryBuilder\Node;

/**
 * Clase/Interfaz Node
 */
abstract class Node implements NodeInterface
{
        protected bool $isEmpty;
        protected array $childs = [];
/**
 * appendChild
 * @param NodeInterface $node
 * @param mixed $name
 * @return mixed
 */

	public function appendChild(NodeInterface $node, $name = null)
	{
		if ($name === null)
			$name = (sizeof($this->childs) > 0)? 1 + count($this->childs) : 0;
		$this->childs[$name] = $node;
		return $name;
	}
/**
 * hasChild
 * @param mixed $name
 * @return mixed
 */

	public function hasChild($name)
	{
		return isset($this->childs[$name]);
	}
/**
 * getChild
 * @param mixed $name
 * @return mixed
 */

	public function getChild($name)
	{
		$node = null;
		if (isset($this->childs[$name])) {
			$node = $this->childs[$name];
		} else {
			$node = new EmptyNode();
		}
		return $node;
	}
/**
 * removeChild
 * @param mixed $name
 * @return mixed
 */

	public function removeChild($name)
	{
		$node = null;
		if (isset($this->childs[$name])) {
			$node = $this->childs[$name];
			unset($this->childs[$name]);
		} else {
			$node = new EmptyNode();
		}
		return $node;
	}
/**
 * allChildren
 * @return mixed
 */

	public function allChildren()
	{
		return $this->childs;
	}
/**
 * isEmpty
 * @return mixed
 */

	public function isEmpty()
	{
		return $this->isEmpty;
	}
/**
 * __clone
 * @return mixed
 */

	public function __clone()
	{
		foreach ($this->childs as $key=>$node)
			$this->childs[$key] = clone $node;
	}
}
