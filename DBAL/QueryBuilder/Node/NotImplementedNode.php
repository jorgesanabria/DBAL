<?php
namespace DBAL\QueryBuilder\Node;

abstract class NotImplementedNode implements NodeInterface
{
	public function appendChild(NodeInterface $node, $name = null)
	{
		return $name;
	}
	public function hasChild($name)
	{
		return false;
	}
	public function getChild($name)
	{
		return new EmptyNode();
	}
	public function removeChild($name)
	{
		return new EmptyNode();
	}
	public function allChildren()
	{
		return [];
	}
	public function isEmpty()
	{
		return $this->isEmpty;
	}
}
