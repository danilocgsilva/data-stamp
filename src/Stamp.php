<?php

namespace Danilocgsilva\DataStamp;

use PDO;
use Danilocgsilva\DataStamp\Utils\CheckTableFields;
use Danilocgsilva\DatabaseDiscover\Field;
use Danilocgsilva\EntitiesDiscover\ForeignRelation;

class Stamp
{
    private PDO $sourcePdo;
    private PDO $targetPdo;

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
        $checkTableFields = new CheckTableFields($this->sourcePdo);
        $foreigns = $checkTableFields->checkForeigns($table);
        if ($this->checkTargetForeignProhibit($foreigns, $table)) {
            throw new DataIntegrityException("There are required fields as foreign keys. Tries to brigns the related model or fetch an existing model to relate in the existing database target.");
        }
        
        $fieldsToUpdate = $checkTableFields->getTableFields($table, $ignoreKey);

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

    private function checkTargetForeignProhibit(array $foreigns, string $table)
    {
        $checkTableFieldsFromTarget = new CheckTableFields($this->targetPdo);
        $targetFields = $checkTableFieldsFromTarget->getTableFields($table);
        
        $notNullableFieldsNames = array_map(
            fn (Field $field) => $field->getName(),
            array_filter(
                $targetFields, 
                fn (Field $field) => $field->getNullData() === "NO"
            )
        );

        $foreignsNames = array_map(
            fn (ForeignRelation $relation) => $relation->getLocalField(),
            $foreigns
        );

        if (array_intersect($foreignsNames, $notNullableFieldsNames)) {
            return true;
        }

        return false;
    }
}
