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

namespace LaravelJsonApi\Core\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Serializable as SerializableContract;
use LaravelJsonApi\Core\Document\Concerns\Serializable;
use LaravelJsonApi\Core\Json\Hash;
use LaravelJsonApi\Core\Responses\Concerns\IsResponsable;
use LogicException;

class MetaResponse implements SerializableContract, Responsable
{

    use IsResponsable;
    use Serializable;

    /**
     * @var int
     */
    private int $status = Response::HTTP_OK;

    /**
     * @param mixed $meta
     * @return static
     */
    public static function make($meta): self
    {
        return new self($meta);
    }

    /**
     * MetaResponse constructor.
     *
     * @param $meta
     */
    public function __construct($meta)
    {
        $this->withMeta($meta);
    }

    /**
     * Add top-level meta to the response.
     *
     * @param mixed $meta
     * @return $this
     */
    public function withMeta($meta): self
    {
        $meta = Hash::cast($meta);

        if ($meta->isEmpty()) {
            throw new LogicException('Meta cannot be empty for a meta response.');
        }

        $this->meta = $meta;

        return $this;
    }

    /**
     * Set the HTTP status for the response.
     *
     * @param int $status
     * @return $this
     */
    public function withStatus(int $status): self
    {
        if (200 > $status || 299 < $status) {
            throw new InvalidArgumentException('Expecting a success status.');
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        $this->defaultJsonApi();

        return new Response(
            $this->toJson($this->encodeOptions),
            $this->status,
            $this->headers()
        );
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array_filter([
            'jsonapi' => $this->jsonApi()->toArray() ?: null,
            'meta' => $this->meta->toArray(),
            'links' => $this->links()->toArray() ?: null,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return array_filter([
            'jsonapi' => $this->jsonApi()->jsonSerialize(),
            'meta' => $this->meta->jsonSerialize(),
            'links' => $this->links()->jsonSerialize(),
        ]);
    }
}
