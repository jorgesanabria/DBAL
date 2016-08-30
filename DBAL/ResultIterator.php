<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class ResultIterator implements \Iterator, \JsonSerializable
{
	protected $pdo;
	protected $message;
	protected $result;
	protected $i;
	protected $stm;
	protected $mappers;
	public function __construct(\PDO $pdo, MessageInterface $message, array $mappers = [])
	{
		$this->pdo = $pdo;
		$this->message = $message;
		$this->mappers = $mappers;
	}
	public function rewind()
	{
		$this->stm = $this->pdo->prepare($this->message->readMessage());
		$this->stm->execute($this->message->getValues());
		$this->result = $this->stm->fetch();
		$this->i = 0;
	}
	public function valid()
	{
		return $this->result !== false;
	}
	public function key()
	{
		return $this->i;
	}
	public function current()
	{
		$result = $this->result;
		foreach ($this->mappers as $mapper)
			$result = call_user_func_array($mapper, [$result]);
		return $result;
	}
	public function next()
	{
		$this->result = $this->stm->fetch();
		$this->i++;
	}
	public function jsonSerialize()
	{
		$this->rewind();
		return $this->stm->fetchAll();
	}
}
