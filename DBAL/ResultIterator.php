<?php
namespace DBAL;

class ResultIterator implements \Iterator, \JsonSerializable
{
	protected $stm;
	protected $values;
	protected $result;
	protected $i;
	public function __construct(\PDOStatement $stm, array $values)
	{
		$this->stm = $stm;
		$this->values = $values;
	}
	public function rewind()
	{
		$this->stm->execute($this->values);
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
		return $this->result;
	}
	public function next()
	{
		$this->result = $this->stm->fetch();
		$this->i++;
	}
	public function jsonSerialize()
	{
		$this->stm->execute($this->values);
		return $this->stm->fetchAll();
	}
}