<?php

namespace Danilocgsilva\DataStamp\Utils;

use PDO;
use Danilocgsilva\DatabaseDiscover\DatabaseDiscover;
use Danilocgsilva\EntitiesDiscover\ErrorLog;
use Danilocgsilva\EntitiesDiscover\Entity;

class CheckTableFields
{
    private DatabaseDiscover $databaseDiscover;
    
    public function __construct(
        private PDO $pdo,
        DatabaseDiscover $databaseDiscover = null
    ) {
        if ($databaseDiscover) {
            $this->databaseDiscover = $databaseDiscover;
        } else {
            $this->databaseDiscover = new DatabaseDiscover();
        }
    }

    public function getTableFields(string $tableName, $ignoreKey = false): array
    {
        $this->databaseDiscover->setPdo($this->pdo);
        $tableFields = [];
        foreach ($this->databaseDiscover->getFieldsFromTable($tableName) as $tableField) {
            $tableFields[] = $tableField;
        }
        if ($ignoreKey) {
            array_shift($tableFields);
        }
        return $tableFields;
    }

    public function checkForeigns(string $tableName): array
    {
        $entity = new Entity(new ErrorLog());
        $entity->setPdo($this->pdo);
        $entity->setTable($tableName);
        $foreigns = [];
        foreach ($entity->getForeigns() as $foreign) {
            $foreigns[] = $foreign;
        }
        return $foreigns;
    }
}