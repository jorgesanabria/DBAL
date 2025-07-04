<?php
declare(strict_types=1);
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
 * @param string|null $name
 * @return string|null
 */

        public function appendChild(NodeInterface $node, $name = null)
        {
                return $name;
        }
/**
 * hasChild
 * @param string $name
 * @return bool
 */

	public function hasChild($name)
	{
		return false;
	}
/**
 * getChild
 * @param string $name
 * @return NodeInterface
 */

	public function getChild($name)
	{
		return new EmptyNode();
	}
/**
 * removeChild
 * @param string $name
 * @return NodeInterface
 */

	public function removeChild($name)
	{
		return new EmptyNode();
	}
/**
 * allChildren
 * @return array
 */

	public function allChildren()
	{
		return [];
	}
/**
 * isEmpty
 * @return bool
 */

	public function isEmpty()
	{
		return $this->isEmpty;
	}
}
