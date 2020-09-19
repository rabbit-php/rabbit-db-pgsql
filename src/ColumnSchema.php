<?php

declare(strict_types=1);

namespace Rabbit\DB\Pgsql;

use Rabbit\DB\ColumnSchema as DBColumnSchema;
use Rabbit\DB\ExpressionInterface;
use Rabbit\DB\JsonExpression;

use function array_walk_recursive;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function strtolower;

final class ColumnSchema extends DBColumnSchema
{
    private int $dimension = 0;

    private ?string $sequenceName = null;

    /**
     * @author Albert <63851587@qq.com>
     * @param [type] $value
     * @return void
     */
    public function dbTypecast($value)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->dimension > 0) {
            return new ArrayExpression($value, $this->dbType, $this->dimension);
        }

        if (in_array($this->dbType, [Schema::TYPE_JSON, Schema::TYPE_JSONB], true)) {
            return new JsonExpression($value, $this->dbType);
        }

        return $this->typecast($value);
    }

    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value
     *
     * @return mixed converted value
     */
    public function phpTypecast($value)
    {
        if ($this->dimension > 0) {
            if (!is_array($value)) {
                $value = $this->getArrayParser()->parse($value);
            }
            if (is_array($value)) {
                array_walk_recursive($value, function (&$val) {
                    $val = $this->phpTypecastValue($val);
                });
            } elseif ($value === null) {
                return null;
            }

            return $value;
        }

        return $this->phpTypecastValue($value);
    }

    /**
     * Casts $value after retrieving from the DBMS to PHP representation.
     *
     * @param string|int|null $value
     *
     * @return bool|mixed|null
     */
    protected function phpTypecastValue($value)
    {
        if ($value === null) {
            return null;
        }

        switch ($this->type) {
            case Schema::TYPE_BOOLEAN:
                $value = is_string($value) ? strtolower($value) : $value;

                switch ($value) {
                    case 't':
                    case 'true':
                        return true;
                    case 'f':
                    case 'false':
                        return false;
                }

                return (bool) $value;
            case Schema::TYPE_JSON:
                return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return parent::phpTypecast($value);
    }

    /**
     * Creates instance of ArrayParser.
     *
     * @return ArrayParser
     */
    protected function getArrayParser(): ArrayParser
    {
        static $parser = null;

        if ($parser === null) {
            $parser = new ArrayParser();
        }

        return $parser;
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }

    public function getSequenceName(): ?string
    {
        return $this->sequenceName;
    }

    public function dimension(int $dimension): void
    {
        $this->dimension = $dimension;
    }

    public function sequenceName(?string $sequenceName): void
    {
        $this->sequenceName = $sequenceName;
    }
}
