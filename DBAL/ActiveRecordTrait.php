<?php
declare(strict_types=1);
namespace DBAL;

trait ActiveRecordTrait
{
    private Crud $crud;
    private array $arOriginal = [];

    public function initActiveRecord(Crud $crud, array $original): void
    {
        $this->crud = $crud;
        $this->arOriginal = $original;
    }

    public function update(): int
    {
        if (!array_key_exists('id', $this->arOriginal)) {
            throw new \RuntimeException('id field missing');
        }

        $current = get_object_vars($this);
        unset($current['crud'], $current['arOriginal']);

        $changed = [];
        foreach ($current as $field => $value) {
            if (!array_key_exists($field, $this->arOriginal) || $this->arOriginal[$field] !== $value) {
                $changed[$field] = $value;
            }
        }

        if (empty($changed)) {
            return 0;
        }

        $count = $this->crud
            ->where(['id__eq' => $this->arOriginal['id']])
            ->update($changed);

        $this->arOriginal = array_merge($this->arOriginal, $changed);

        return $count;
    }

    public function jsonSerialize(): array
    {
        $data = get_object_vars($this);
        unset($data['crud'], $data['arOriginal']);
        return $data;
    }
}
