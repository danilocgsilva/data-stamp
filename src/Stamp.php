<?php

namespace Danilocgsilva\DataStamp;

use PDO;
use Danilocgsilva\DatabaseDiscover\DatabaseDiscover;

class Stamp
{
    private PDO $sourcePdo;
    private PDO $targetPdo;
    private DatabaseDiscover $databaseDiscover;
    private bool $ignoreKey;

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

    public function stamp(string $table, int $id, $ignoreKey = false): void
    {
        $this->ignoreKey = $ignoreKey;
        $fieldsToUpdate = $this->getTableFields($table);
        $valuesToUpdateFromSource = $this->getValuesInQuery($id, $fieldsToUpdate, $table);

        $queryBase = "INSERT INTO %s (%s) VALUES (%s)";
        $updateQuery = sprintf(
            $queryBase,
            $table,
            implode(", ", $fieldsToUpdate),
            implode(", ", $valuesToUpdateFromSource)
        );
        
        $results = $this->targetPdo->prepare($updateQuery);
        $results->execute();
    }

    private function getTableFields(string $tableName): array
    {
        $this->databaseDiscover->setPdo($this->sourcePdo);
        $tableFields = [];
        foreach ($this->databaseDiscover->getFieldsFromTable($tableName) as $tableField) {
            $tableFields[] = $tableField;
        }
        if ($this->ignoreKey) {
            array_shift($tableFields);
        }
        return $tableFields;
    }

    private function getValuesInQuery(int $entityId, array $tableFields, string $tableName): array
    {
        $tableFieldsBaseQuery = $this->convertFieldsToQuerySelectFieldsPart($tableFields);
        $sqlQuery = "SELECT $tableFieldsBaseQuery FROM $tableName WHERE id = $entityId";
        $results = $this->sourcePdo->prepare($sqlQuery);
        $results->execute();
        $rowResult = $results->fetch(PDO::FETCH_ASSOC);

        $contentQuery = [];
        foreach ($tableFields as $tableFiled) {
            $contentQuery[] = $this->writeFieldByType(
                $rowResult, 
                $tableFiled->getName(), 
                $tableFiled->getType()
            );
        }

        if ($this->ignoreKey) {
            array_shift($contentQuery);
        }

        return $contentQuery;
    }

    private function convertFieldsToQuerySelectFieldsPart(array $fields): string
    {
        return implode(", ", $fields);
    }

    private function isString(string $type): bool
    {
        return $type !== "int(11)";
    }

    private function writeFieldByType(array $rowResult, string $fieldName, string $fieldType): string
    {
        $contentBaseField = $rowResult[$fieldName];
        if ($contentBaseField === null) {
            return "null";
        }
        if ($this->isString($fieldType)) {
            $contentBaseField = "\"" . $contentBaseField . "\"";
        }
        return $contentBaseField;
    }
}
