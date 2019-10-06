<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries;

use Palicao\PhpRedisTimeSeries\Exception\InvalidFilterOperationException;

class Filter
{
    public const OP_EQUALS = 0;
    public const OP_NOT_EQUALS = 1;
    public const OP_EXISTS = 2;
    public const OP_NOT_EXISTS = 3;
    public const OP_IN = 4;
    public const OP_NOT_IN = 5;

    private const OPERATIONS = [
        self::OP_EQUALS,
        self::OP_NOT_EQUALS,
        self::OP_EXISTS,
        self::OP_NOT_EXISTS,
        self::OP_IN,
        self::OP_NOT_IN
    ];

    /** @var array */
    private $filters = [];

    public function __construct(string $label, string $value)
    {
        $this->filters[] = [$label, self::OP_EQUALS, $value];
    }

    /**
     * @param string $label
     * @param int $operation
     * @param string|array|null $value
     */
    public function add(string $label, int $operation, $value = null): void
    {
        if (!in_array($operation, self::OPERATIONS, true)) {
            throw new InvalidFilterOperationException('Operation is not valid');
        }

        if (in_array($operation, [self::OP_EQUALS, self::OP_NOT_EQUALS], true) && !is_string($value)) {
            throw new InvalidFilterOperationException('The provided operation requires the value to be string');
        }

        if (in_array($operation, [self::OP_EXISTS, self::OP_NOT_EXISTS], true) && $value !== null) {
            throw new InvalidFilterOperationException('The provided operation requires the value to be null');
        }

        if (in_array($operation, [self::OP_IN, self::OP_NOT_IN], true) && !is_array($value)) {
            throw new InvalidFilterOperationException('The provided operation requires the value to be an array');
        }

        $this->filters[] = [$label, $operation, $value];
    }

    public function toRedisParams() : string
    {
        $params = [];
        foreach ($this->filters as $filter) {
            switch ($filter[1]) {
                case self::OP_EQUALS:
                    $params[] = $filter[0] . '=' . $filter[2];
                    break;
                case self::OP_NOT_EQUALS:
                    $params[] = $filter[0] . '!=' . $filter[2];
                    break;
                case self::OP_EXISTS:
                    $params[] = $filter[0] . '=';
                    break;
                case self::OP_NOT_EXISTS:
                    $params[] = $filter[0] . '!=';
                    break;
                case self::OP_IN:
                    $params[] = $filter[0] . '=(' . implode(',', $filter[2]) . ')';
                    break;
                case self::OP_NOT_IN:
                    $params[] = $filter[0] . '!=(' . implode(',', $filter[2]) . ')';
                    break;
            }
        }
        return implode(' ', $params);
    }
}