<?php
namespace DBAL;

use DBAL\QueryBuilder\Query;

class Crud extends Query
{
	protected $connection;
	protected $mapers = [];
	public function __construct(\PDO $connection)
	{
		$this->connection = $connection;
		parent::__construct();
	}
	public function map(callable $callback)
	{
		$clon = clone $this;
		$clon->mapers[] = $callback;
		return $clon;
	}
	public function select(...$fields)
	{
		$message = $this->buildSelect(...$fields);
		$stm = $this->connection->prepare($message->readMessage());
		if ($this->entity_class !== null)
			$stm->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, $this->entity_class, $entity_di);
		else
			$stm->setFetchMode(\PDO::FETCH_ASSOC);
		return new ResultIterator($stm, $message->getValues());
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
