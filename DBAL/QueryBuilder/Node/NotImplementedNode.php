<?php
namespace DBAL\QueryBuilder\Node;

/**
 * Helper class for nodes that do not implement certain features.
 *
 * It provides empty implementations of the {@see NodeInterface} methods. Child
 * classes may override only the methods they need.
 */
abstract class NotImplementedNode implements NodeInterface
{
        protected bool $isEmpty = true;
/**
 * appendChild
 * @param NodeInterface $node
 * @param mixed $name
 * @return mixed
 */

        public function appendChild(NodeInterface $node, $name = null)
        {
                return $name;
        }
/**
 * hasChild
 * @param mixed $name
 * @return mixed
 */

	public function hasChild($name)
	{
		return false;
	}
/**
 * getChild
 * @param mixed $name
 * @return mixed
 */

	public function getChild($name)
	{
		return new EmptyNode();
	}
/**
 * removeChild
 * @param mixed $name
 * @return mixed
 */

	public function removeChild($name)
	{
		return new EmptyNode();
	}
/**
 * allChildren
 * @return mixed
 */

	public function allChildren()
	{
		return [];
	}
/**
 * isEmpty
 * @return mixed
 */

	public function isEmpty()
	{
		return $this->isEmpty;
	}
}
