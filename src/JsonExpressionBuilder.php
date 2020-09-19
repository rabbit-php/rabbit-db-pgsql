<?php

declare(strict_types=1);

namespace Rabbit\DB\Pgsql;

use Rabbit\Base\Helper\JsonHelper;
use Rabbit\DB\ArrayExpression;
use Rabbit\DB\ExpressionBuilderInterface;
use Rabbit\DB\ExpressionBuilderTrait;
use Rabbit\DB\ExpressionInterface;
use Rabbit\DB\JsonExpression;
use Rabbit\DB\Query;

final class JsonExpressionBuilder implements ExpressionBuilderInterface
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

        if ($value instanceof Query) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return "($sql)" . $this->getTypecast($expression);
        }

        if ($value instanceof ArrayExpression) {
            $placeholder = 'array_to_json(' . $this->queryBuilder->buildExpression($value, $params) . ')';
        } else {
            $placeholder = $this->queryBuilder->bindParam(JsonHelper::encode($value), $params);
        }

        return $placeholder . $this->getTypecast($expression);
    }
    /**
     * @author Albert <63851587@qq.com>
     * @param JsonExpression $expression
     * @return string
     */
    protected function getTypecast(JsonExpression $expression): string
    {
        if ($expression->getType() === null) {
            return '';
        }

        return '::' . $expression->getType();
    }
}
