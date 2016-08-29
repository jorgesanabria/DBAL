<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

interface NodeInterface
{
	/**
	* Send message to node
	* @param MessageInterface the message to send
	* @return MessageInterface the response message
	*/
	public function send(MessageInterface $message);

	/**
	* Append child node to list childs
	* @param NodeInterface the node to append
	* @param string the node name
	* @return string the node name
	*/
	public function appendChild(NodeInterface $node, $name = null);

	/**
	* Return true if exist the child node. Return false if is not existst or is not implemented
	* @param string the node name
	* @return bool
	*/
	public function hasChild($name);

	/**
	* Get child node of list childs or empty node if is not exists (or not implemented)
	* @param string the node name
	* @return NodeInterface the child node
	*/
	public function getChild($name);

	/**
	* Remove a child node named or empty node if is not exists (or not implemented)
	* @param the node name
	* @return NodeInterface
	*/
	public function removeChild($name);

	/**
	* Get all child nodes or empty array if is not exists (or not implemented)
	* @return NodeInterface[]|[] the list of childs or empty array if is not implemented
	*/
	public function allChildren();

	/**
	* Return true if this node is empty node
	* @return bool
	*/
	public function isEmpty();
}