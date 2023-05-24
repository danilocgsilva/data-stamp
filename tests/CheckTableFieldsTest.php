<?php

namespace Danilocgsilva\DataStamp\Tests;

use Danilocgsilva\DataStamp\Utils\CheckTableFields;
use PHPUnit\Framework\TestCase;
use PDOStatement;

class CheckTableFieldsTest extends TestCase
{
    use PDOMocksTrait;

    public function testGetTableFields()
    {
        $pdoMocked = $this->getPdoMocked();

        $pdoMocked
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->buildFieldsTablePdoStatement());

        $checkTableFields = new CheckTableFields($pdoMocked);

        $tableFields = $checkTableFields->getTableFields('drivers');

        $this->assertIsArray($tableFields);
    }

    public function testCheckForeigns()
    {
        $pdoMocked = $this->getPdoMocked();

        $pdoMocked
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->buildRelationsPdoStatementToFetchRelations());

        $pdoMocked
            ->expects($this->once())
            ->method('query')
            ->willReturn($this->buildRelationsPdoStatement());

        $checkTableFields = new CheckTableFields($pdoMocked);
        $foreigns = $checkTableFields->checkForeigns('users');

        $this->assertIsArray($foreigns);
    }

    private function buildFieldsTablePdoStatement(): PDOStatement
    {
        $pdoStatementMock = $this->getMockBuilder(PDOStatement::class)
            ->getMock();

        $pdoStatementMock
            ->expects($this->once())
            ->method('execute');

        $pdoStatementMock
            ->expects($this->once())
            ->method('fetch');

        return $pdoStatementMock;
    }

    private function buildRelationsPdoStatement(): PDOStatement
    {
        $pdoStatementMock = $this->getMockBuilder(PDOStatement::class)
            ->getMock();

        return $pdoStatementMock;
    }

    private function buildRelationsPdoStatementToFetchRelations(): PDOStatement
    {
        $pdoStatementMock = $this->buildRelationsPdoStatement();

        $pdoStatementMock
            ->expects($this->once())
            ->method('execute');
        $pdoStatementMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([]);

        return $pdoStatementMock;
    }
}
