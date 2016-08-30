<?php
namespace DBAL;

use DBAL\QueryBuilder\Query;

class Crud extends Query
{
	protected $connection;
	protected $mappers = [];
	public function __construct(\PDO $connection)
	{
		$this->connection = $connection;
		parent::__construct();
	}
	public function map(callable $callback)
	{
		$clon = clone $this;
		$clon->mappers[] = $callback;
		return $clon;
	}
	public function select(...$fields)
	{
		$message = $this->buildSelect(...$fields);
		return new ResultIterator($this->connection, $message, $this->mappers);
	}
	public function insert(array $fields)
	{
		$message = $this->buildInsert($fields);
		$stm = $this->connection->prepare($message->readMessage());
		$stm->execute($message->getValues());
		return $this->connection->lastInsertId();
	}
	public function update(array $fields)
	{
		$message = $this->buildUpdate($fields);
		$stm = $this->connection->prepare($message->readMessage());
		$stm->execute($message->getValues());
		return $stm->rowCount();
	}
	public function delete()
	{
		$message = $this->buildInsert($fields);
		$stm = $this->connection->prepare($message->readMessage());
		$stm->execute($message->getValues());
		return $stm->rowCount();
	}
}
