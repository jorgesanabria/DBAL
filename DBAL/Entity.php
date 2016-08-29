<?php
namespace DBAL;

use DBAL\QueryBuilder\Query;

class Entity
{
	protected $table;
	protected $query;
	protected $fields = [];
	public function __construct(string $table, Query $query)
	{
		$this->table = $table;
		$this->query = $query;
	}
	public function __set(string $name, $value)
	{
		$this->fields[$name] = $value;
	}
	public function __get(string $name)
	{
		return isset($this->fields[$name])? $this->fields[$name] : null;
	}
	public function save(string ...$primaryKeys)
	{
		if (empty($primaryKeys))
			$primaryKeys = ['id'];
		$q = $this->query->from($this->table);
		$fields = $this->fields;
		foreach ($primaryKeys as $primaryKey)
		{
			$q = $q->where([$primaryKey=>$this->fields[$primaryKey]]);
			unset($fields[$primaryKey]);
		}
		return $q->update($fields);
	}
	protected function belongsTo(string $table, string $related_id)
	{
		if (empty($related_id))
			$related_id = sprintf('%s_id', $table);
		return $this->query->from($table)->where(['id'=>$this->fields[$related_id]])->firts();
	}
	protected function oneToOne(string $table, string $related_id)
	{
		if (empty($related_id))
			$related_id = sprintf('%s_id', $this->table);
		return $this->query->from($table)->where([$related_id=>$this->fields['id']])->firts();
	}
	protected function oneMany(string $table, string $related_id)
	{
		return $this->query->from($table)->where([$related_id=>$this->fields['id']]);
	}
	protected function manyToMany(string $table, string $intermediary, string $related_id_a, string $related_id_b)
	{
		if (empty($related_id_a))
			$related_id = sprintf('%s_id', $table);
		if (empty($related_id_b))
			$related_id = sprintf('%s_id', $this->table);
		return $this
			->query
			->from($table)
			->innerJoin(
				$intermediary,
				[sprintf('%s.%s__eqf', $table, $related_id_a)=>sprintf('%s.%s', $intermediary, $related_id_a)]
			)
			->where([sprintf('%s.%s', $intermediary, $related_id_b)=>$this->fields['id']]);
	}
}