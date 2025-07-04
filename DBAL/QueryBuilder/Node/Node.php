<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

/**
 * Base implementation for all query builder nodes.
 *
 * Nodes form a tree structure where each node is responsible for generating a
 * fragment of SQL. Child nodes can be appended and will be processed when
 * {@see NodeInterface::send()} is executed.
 */
abstract class Node implements NodeInterface
{
        /** @var bool Indicates if the node produces any SQL fragment */
        protected bool $isEmpty;

        /** @var array<string|int, NodeInterface> */
        protected array $childs = [];
        /**
         * Append a child node to this node.
         *
         * @param NodeInterface   $node Node to append.
         * @param string|int|null $name Optional child name. When null a numeric
         *                              index will be generated.
         * @return string|int              The assigned name.
         */
        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($name === null) {
                        $name = (count($this->childs) > 0) ? 1 + count($this->childs) : 0;
                }
                $this->childs[$name] = $node;
                return $name;
        }
        /**
         * Check if a child with the given name exists.
         *
         * @param string|int $name Child node name.
         * @return bool
         */
        public function hasChild($name)
        {
                return isset($this->childs[$name]);
        }
        /**
         * Retrieve a child node or an {@see EmptyNode} if it does not exist.
         *
         * @param string|int $name Child node name.
         * @return NodeInterface
         */
        public function getChild($name)
        {
                if (isset($this->childs[$name])) {
                        return $this->childs[$name];
                }
                return new EmptyNode();
        }
        /**
         * Remove a child node from this node.
         *
         * @param string|int $name Child node name.
         * @return NodeInterface The removed node or an EmptyNode if not found.
         */
        public function removeChild($name)
        {
                if (isset($this->childs[$name])) {
                        $node = $this->childs[$name];
                        unset($this->childs[$name]);
                        return $node;
                }
                return new EmptyNode();
        }
        /**
         * Return all child nodes.
         *
         * @return array<string|int, NodeInterface>
         */
        public function allChildren()
        {
                return $this->childs;
        }
        /**
         * Indicate if this node is considered empty.
         */
        public function isEmpty()
        {
                return $this->isEmpty;
        }
        /**
         * Deep clone child nodes when cloning.
         */
        public function __clone()
        {
                foreach ($this->childs as $key => $node) {
                        $this->childs[$key] = clone $node;
                }
        }
}
