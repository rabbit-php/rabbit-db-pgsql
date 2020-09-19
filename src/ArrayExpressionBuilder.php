<?php

declare(strict_types=1);

namespace Rabbit\DB\Pgsql;

use Rabbit\DB\ArrayExpression;
use Rabbit\DB\ExpressionBuilderInterface;
use Rabbit\DB\ExpressionBuilderTrait;
use Rabbit\DB\ExpressionInterface;
use Rabbit\DB\JsonExpression;
use Rabbit\DB\Query;
use Traversable;

use function get_class;
use function implode;
use function in_array;
use function is_array;
use function str_repeat;

final class ArrayExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * @author Albert <63851587@qq.com>
     * @param ExpressionInterface $expression
     * @param array $params
     * @return string
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $expression->getValue();
        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof Query) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return $this->buildSubqueryArray($sql, $expression);
        }

        $placeholders = $this->buildPlaceholders($expression, $params);

        return 'ARRAY[' . implode(', ', $placeholders) . ']' . $this->getTypehint($expression);
    }

    /**
     * @author Albert <63851587@qq.com>
     * @param ExpressionInterface $expression
     * @param [type] $params
     * @return array
     */
    protected function buildPlaceholders(ExpressionInterface $expression, &$params): array
    {
        $value = $expression->getValue();

        $placeholders = [];
        if ($value === null || (!is_array($value) && !$value instanceof Traversable)) {
            return $placeholders;
        }

        if ($expression->getDimension() > 1) {
            foreach ($value as $item) {
                $placeholders[] = $this->build($this->unnestArrayExpression($expression, $item), $params);
            }
            return $placeholders;
        }

        foreach ($value as $item) {
            if ($item instanceof Query) {
                [$sql, $params] = $this->queryBuilder->build($item, $params);
                $placeholders[] = $this->buildSubqueryArray($sql, $expression);
                continue;
            }

            $item = $this->typecastValue($expression, $item);

            if ($item instanceof ExpressionInterface) {
                $placeholders[] = $this->queryBuilder->buildExpression($item, $params);
                continue;
            }

            $placeholders[] = $this->queryBuilder->bindParam($item, $params);
        }

        return $placeholders;
    }

    private function unnestArrayExpression(ArrayExpression $expression, $value): ArrayExpression
    {
        $expressionClass = get_class($expression);

        return new $expressionClass($value, $expression->getType(), $expression->getDimension() - 1);
    }

    protected function getTypeHint(ArrayExpression $expression): string
    {
        if ($expression->getType() === null) {
            return '';
        }

        $result = '::' . $expression->getType();
        $result .= str_repeat('[]', $expression->getDimension());

        return $result;
    }

    /**
     * Build an array expression from a subquery SQL.
     *
     * @param string $sql the subquery SQL.
     * @param ArrayExpression $expression
     *
     * @return string the subquery array expression.
     */
    protected function buildSubqueryArray(string $sql, ArrayExpression $expression): string
    {
        return 'ARRAY(' . $sql . ')' . $this->getTypeHint($expression);
    }

    /**
     * Casts $value to use in $expression.
     *
     * @param ArrayExpression $expression
     * @param mixed $value
     *
     * @return int|JsonExpression
     */
    protected function typecastValue(ArrayExpression $expression, $value)
    {
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if (in_array($expression->getType(), [Schema::TYPE_JSON, Schema::TYPE_JSONB], true)) {
            return new JsonExpression($value);
        }

        return $value;
    }
}