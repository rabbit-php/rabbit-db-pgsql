<?php

declare(strict_types=1);

namespace Rabbit\DB\Pgsql;

use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\DB\ConnectionInterface;

/**
 * Class Connection
 * @package rabbit\db\mysql
 */
class Connection extends \Rabbit\DB\Connection implements ConnectionInterface
{
    public array $schemaMap = [
        'pgsql'
    ];

    /**
     * @author Albert <63851587@qq.com>
     * @param string $dsn
     * @param string $poolKey
     */
    public function __construct(string $dsn, string $poolKey)
    {
        parent::__construct($dsn);
        $this->poolKey = $poolKey;
        $this->driver = 'pgsql';
    }

    /**
     * @return PDO
     */
    public function createPdoInstance()
    {
        $parsed = $this->parseDsn;
        isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = [];
        [$_, $host, $port, $this->username, $this->password, $query] = ArrayHelper::getValueByArray(
            $parsed,
            ['scheme', 'host', 'port', 'user', 'pass', 'query'],
            ['pgsql', '127.0.0.1', '3306', '', '', []]
        );
        $parts = [];
        foreach ($query as $key => $value) {
            $parts[] = "$key=$value";
        }
        $dsn = "host=$host;port=$port;user=$this->username;password=$this->password;" . implode(';', $parts);
        $pdo = new \Swoole\Coroutine\PostgreSQL($dsn);
        return $pdo;
    }
}
