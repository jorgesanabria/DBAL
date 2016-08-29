<?php
namespace DBAL\QueryBuilder\Node;

abstract class Node implements NodeInterface
{
	protected $isEmpty;
	protected $childs = [];
	public function appendChild(NodeInterface $node, $name = null)
	{
		if ($name === null)
			$name = (sizeof($this->childs) > 0)? 1 + count($this->childs) : 0;
		$this->childs[$name] = $node;
		return $name;
	}
	public function hasChild($name)
	{
		return isset($this->childs[$name]);
	}
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
	public function allChildren()
	{
		return $this->childs;
	}
	public function isEmpty()
	{
		return $this->isEmpty;
	}
	public function __clone()
	{
		foreach ($this->childs as $key=>$node)
			$this->childs[$key] = clone $node;
	}
}