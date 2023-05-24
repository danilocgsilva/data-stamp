<?php

namespace Danilocgsilva\DataStamp\Tests;

use PDO;
use PDOStatement;

trait PDOMocksTrait
{
    private function getPdoMocked()
    {
        $pdoSource = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $pdoSource;
    }

}
