<?php
/*
 * Copyright 2021 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Query;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\Schema;
use UnexpectedValueException;

class FilterParameters implements \IteratorAggregate, \Countable, Arrayable
{

    /**
     * @var FilterParameter[]
     */
    private array $stack;

    /**
     * @param FilterParameters|FilterParameter|Enumerable|array|null $value
     * @return static
     */
    public static function cast($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_array($value) || $value instanceof Enumerable) {
            return self::fromArray($value);
        }

        if ($value instanceof FilterParameter) {
            return new self($value);
        }

        if (is_null($value)) {
            return new self();
        }

        throw new UnexpectedValueException('Cannot cast value to filter parameters.');
    }

    /**
     * @param array|Enumerable $parameters
     * @return static
     */
    public static function fromArray($parameters): self
    {
        if ($parameters instanceof Enumerable) {
            $parameters = $parameters->all();
        }

        if (!is_array($parameters)) {
            throw new InvalidArgumentException('Expecting an array or enumerable.');
        }

        $filters = new self();

        foreach ($parameters as $key => $value) {
            $filters->stack[$key] = new FilterParameter($key, $value);
        }

        return $filters;
    }

    /**
     * @param $value
     * @return static|null
     */
    public static function nullable($value): ?self
    {
        if (!is_null($value)) {
            return self::cast($value);
        }

        return null;
    }

    /**
     * FilterParameters constructor.
     *
     * @param FilterParameter ...$parameters
     */
    public function __construct(FilterParameter ...$parameters)
    {
        $this->stack = [];

        foreach ($parameters as $parameter) {
            $this->stack[$parameter->key()] = $parameter;
        }
    }

    /**
     * @param string $key
     * @return FilterParameter|null
     */
    public function get(string $key): ?FilterParameter
    {
        return $this->stack[$key] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return isset($this->stack[$key]);
    }

    /**
     * @return FilterParameter[]
     */
    public function all(): array
    {
        return $this->stack;
    }

    /**
     * @return Collection
     */
    public function collect(): Collection
    {
        return Collection::make($this->stack);
    }

    /**
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->stack);
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function filter(callable $callback): self
    {
        $copy = new self();
        $copy->stack = $this->collect()->filter($callback)->all();

        return $copy;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function reject(callable $callback): self
    {
        $copy = new self();
        $copy->stack = $this->collect()->reject($callback)->all();

        return $copy;
    }

    /**
     * @param Schema $schema
     * @return $this
     */
    public function forSchema(Schema $schema): self
    {
        return $this->filter(
            static fn(FilterParameter $parameter) => $parameter->existsOnSchema($schema)
        );
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array_map(
            static fn(FilterParameter $parameter) => $parameter->value(),
            $this->stack,
        );
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->stack);
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        yield from $this->stack;
    }

}
