<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Server;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use LaravelJsonApi\Core\Server\ServerRepository;
use LaravelJsonApi\Core\Support\AppResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServerRepositoryTest extends TestCase
{
    /**
     * @var MockObject&Application
     */
    private Application&MockObject $app;

    /**
     * @var MockObject&ConfigRepository
     */
    private ConfigRepository&MockObject $config;

    /**
     * @var AppResolver
     */
    private AppResolver $resolver;

    /**
     * @var ServerRepository
     */
    private ServerRepository $repository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->createMock(Application::class);
        $this->config = $this->createMock(ConfigRepository::class);
        $this->resolver = new AppResolver(fn() => $this->app);
        $this->repository = new ServerRepository($this->resolver);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $expected = $this->willMakeServer($name = 'v1');
        $actual = $this->repository->server($name);

        $this->assertInstanceOf(TestServer::class, $actual);
        $this->assertEquals($expected, $actual);
        $this->assertSame($actual, $this->repository->server($name)); // server should be thread cached.
    }

    /**
     * @return void
     */
    public function testItCanUseServerOnce1(): void
    {
        $this->willMakeServer($name = 'v2');

        $server1 = $this->repository->once($name);
        $server2 = $this->repository->server($name);
        $server3 = $this->repository->server($name);

        $this->assertNotSame($server1, $server2);
        $this->assertNotSame($server1, $server3);
        $this->assertSame($server2, $server3);
    }

    /**
     * @return void
     */
    public function testItCanUseServerOnce2(): void
    {
        $this->willMakeServer($name = 'v2');

        $server1 = $this->repository->server($name);
        $server2 = $this->repository->once($name);
        $server3 = $this->repository->server($name);

        $this->assertNotSame($server1, $server2);
        $this->assertNotSame($server2, $server3);
        $this->assertSame($server1, $server3);
    }

    /**
     * @return void
     */
    public function testItHasInvalidClassNameForServer(): void
    {
        $this->willMakeServer($name = 'invalid', \DateTimeImmutable::class);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage("JSON:API server 'invalid' does not exist in config or is not a valid class.");

        $this->repository->server($name);
    }

    /**
     * @param string $name
     * @param string $class
     * @return TestServer
     */
    private function willMakeServer(string $name, string $class = TestServer::class): TestServer
    {
        $this->app
            ->method('make')
            ->with(ConfigRepository::class)
            ->willReturn($this->config);

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with("jsonapi.servers.{$name}")
            ->willReturn($class);

        return new TestServer($this->resolver, $name);
    }
}
