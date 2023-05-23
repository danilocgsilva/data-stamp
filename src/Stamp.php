<?php

namespace Danilocgsilva\DataStamp;

use PDO;
use Danilocgsilva\DatabaseDiscover\DatabaseDiscover;

class Stamp
{
    private PDO $sourcePdo;
    private PDO $targetPdo;
    private DatabaseDiscover $databaseDiscover;

    public function __construct(DatabaseDiscover $databaseDiscover = null)
    {
        if ($databaseDiscover) {
            $this->databaseDiscover = $databaseDiscover;
        } else {
            $this->databaseDiscover = new DatabaseDiscover();
        }
    }
    
    public function setSource(PDO $sourcePdo): self
    {
        $this->sourcePdo = $sourcePdo;
        return $this;
    }

    public function setTarget(PDO $targetPdo): self
    {
        $this->targetPdo = $targetPdo;
        return $this;
    }

    public function stamp(string $table, int $id): void
    {
        $fieldsToUpdate = $this->getTableFields($table);
        $valuesInUpdate = $this->getValuesInQuery($id, $fieldsToUpdate, $table);

        $query = "INSERT INTO %s VALUES %s";
    }

    private function getTableFields(string $tableName): array
    {
        $this->databaseDiscover->setPdo($this->sourcePdo);
        $tableFields = [];
        foreach ($this->databaseDiscover->getFieldsFromTable($tableName) as $tableField) {
            $tableFields[] = $tableField;
        }
        return $tableFields;
    }

    private function getValuesInQuery(int $entityId, array $tableFields, string $tableName): array
    {
        $tableFieldsBaseQuery = $this->convertFieldsToQuerySelectFieldsPart($tableFields);
        $sqlQuery = "SELECT $tableFieldsBaseQuery FROM $tableName WHERE id = $entityId";
    }

    private function convertFieldsToQuerySelectFieldsPart(array $fields): string
    {
        return implode(", ", $fields);
    }
}
