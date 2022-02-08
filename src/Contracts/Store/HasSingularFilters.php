<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Contracts\Store;

use LaravelJsonApi\Contracts\Pagination\Page;

interface HasSingularFilters
{

    /**
     * Execute the query and return the result.
     *
     * If a singular filter has been applied, this method MUST return
     * the first matching model, or null.
     *
     * Otherwise, this method MUST return an iterable of all matching models.
     *
     * @return iterable|object|null
     */
    public function firstOrMany();

    /**
     * Execute the query, with support for singular filters.
     *
     * If the supplied page variable is empty, this method MUST return:
     *
     * - the first matching model or null if a singular filter has been applied; OR
     * - a page of matching models, if default pagination is always used; OR
     * - all the matching models.
     *
     * If the supplied page variable is not empty AND pagination is supported,
     * this method MUST return a page of matching models.
     *
     * @param array|null $page
     * @return object|Page|iterable|null
     */
    public function firstOrPaginate(?array $page);
}
