<?php

declare(strict_types=1);

namespace Rabbit\DB\Pgsql;

use Throwable;
use PDOException;
use Rabbit\DB\Exception;
use Rabbit\DB\Command as DBCommand;

class Command extends DBCommand
{
    public function prepare(bool $forRead = null)
    {
        if ($this->pdoStatement) {
            $this->bindPendingParams();
            return;
        }

        $sql = $this->sql;

        if ($this->db->getTransaction()) {
            // master is in a transaction. use the same connection.
            $forRead = false;
        }

        if ($forRead || $forRead === null && $this->db->getSchema()->isReadQuery($sql)) {
            $pdo = $this->db->getSlavePdo();
        } else {
            $pdo = $this->db->getMasterPdo();
        }
        if ($pdo === null) {
            throw new Exception('Can not get the connection!');
        }
        try {
            $this->pdoStatement = $pdo->prepare(uniqid(), $sql);
            $this->bindPendingParams();
        } catch (Throwable $e) {
            $message = $e->getMessage() . " Failed to prepare SQL: $sql";
            $errorInfo = $e instanceof PDOException ? $e->errorInfo : null;
            $e = new Exception($message, $errorInfo, (int)$e->getCode(), $e);
            throw $e;
        }
    }
}
