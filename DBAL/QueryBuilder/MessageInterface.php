<?php
namespace DBAL\QueryBuilder;

/**
 * Clase/Interfaz MessageInterface
 */
interface MessageInterface
{
	const SEPARATOR_SPACE = ' ';
	const SEPARATOR_AND   = ' AND ';
	const SEPARATOR_OR    = ' OR ';
	const SEPARATOR_NOT   = ' NOT ';
	const SEPARATOR_COMMA   = ', ';
	const MESSAGE_TYPE_SELECT = 1;
	const MESSAGE_TYPE_INSERT = 2;
	const MESSAGE_TYPE_UPDATE = 3;
	const MESSAGE_TYPE_DELETE = 4;

	/**
	* Join this message with message passed to method
	* @param MessageInterface the message for join
	* @param string the separator string
	* @return MessageInterface a new MessageInterface instance
	*/
	public function join(MessageInterface $message, $separator = MessageInterface::SEPARATOR_SPACE);

	/**
	* Insert string in to start message
	* @param string the string to insert
	* @param string the separator string
	* @return MessageInterface a new MessageInterface instance
	*/
	public function insertBefore($string, $separator = MessageInterface::SEPARATOR_SPACE);

	/**
	* Replace original old string for now string in this mesage
	* @param string the original string
	* @param string the new string
	* @return MessageInterface a new MessageInterface instance
	*/
	public function replace($old, $now);
	
	/**
	* Insert string in to end message
	* @param string the string to insert
	* @param string the separator string
	* @return MessageInterface a new MessageInterface instance
	*/
	public function insertAfter($string, $separator = MessageInterface::SEPARATOR_SPACE);

	/**
	* Add values to list of values
	* @param array the values
	* @return MessageInterface a new MessageInterface instance
	*/
	public function addValues(array $values);

	/**
	* Return the message values
	* @return mixed[] the message values
	*/
	public function getValues();

	/**
	* Return the num message values
	* @return int the number of message values
	*/
	public function numValues();

	/**
	* Return the message string
	* @return string the string to message
	*/
	public function readMessage();

	/**
	* Get length of string message
	* @return int the length of message
	*/
	public function getLength();


	/**
	* Return the message type
	* @return int the type of message
	*/
	public function type();
}
