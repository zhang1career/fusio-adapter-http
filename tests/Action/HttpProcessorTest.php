<?php
/*
 * Fusio is an open source API management platform which helps to create innovative API solutions.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Fusio\Adapter\Http\Tests\Action;

use Composer\InstalledVersions;
use Fusio\Adapter\Http\Action\HttpProcessor;
use Fusio\Adapter\Http\Action\HttpSenderAbstract;
use Fusio\Engine\Context;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Model\App;
use Fusio\Engine\Model\User;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use Fusio\Engine\Test\EngineTestCaseTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PSX\Record\Record;

/**
 * HttpProcessorTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class HttpProcessorTest extends HttpActionTestCase
{
    public function testForwardsClientAuthorizationWhenOperationUsabilityIsExternal(): void
    {
        $transactions = [];
        $history = Middleware::history($transactions);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true])),
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $client = new Client(['handler' => $handler]);

        $action = $this->getActionFactory()->factory($this->getActionClass());
        if ($action instanceof HttpSenderAbstract) {
            $action->setClient($client);
        }

        $url = 'http://127.0.0.1';
        $response = $this->handle(
            $action,
            $this->getRequest(
                'GET',
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer upstream-from-client',
                ],
                Record::fromArray(['foo' => 'bar'])
            ),
            $this->getParameters($this->getConfiguration($url)),
            $this->getContextWithExternalOperation()
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $transactions);
        $transaction = reset($transactions);

        $headers = [
            'x-fusio-operation-id' => ['34'],
            'x-fusio-user-anonymous' => ['0'],
            'x-fusio-user-id' => ['2'],
            'x-fusio-user-name' => ['Consumer'],
            'x-fusio-app-id' => ['3'],
            'x-fusio-app-key' => ['5347307d-d801-4075-9aaa-a21a29a448c5'],
            'x-fusio-remote-ip' => ['127.0.0.1'],
            'x-forwarded-for' => ['127.0.0.1'],
            'accept' => ['application/json, application/x-www-form-urlencoded;q=0.9, */*;q=0.8'],
            'user-agent' => ['Fusio Adapter-HTTP v' . InstalledVersions::getVersion('fusio/adapter-http')],
            'authorization' => ['Bearer upstream-from-client'],
        ];

        $this->assertEquals($headers, $this->getXHeaders($transaction['request']->getHeaders()));
    }

    protected function getActionClass(): string
    {
        return HttpProcessor::class;
    }

    private function getContextWithExternalOperation(): ContextInterface
    {
        $app = new App(
            anonymous: false,
            id: 3,
            userId: 2,
            status: 1,
            name: 'Foo-App',
            url: 'http://google.com',
            appKey: '5347307d-d801-4075-9aaa-a21a29a448c5',
            parameters: ['foo' => 'bar'],
            scopes: ['foo', 'bar'],
        );

        $user = new User(
            anonymous: false,
            id: 2,
            roleId: 1,
            categoryId: 1,
            status: 0,
            name: 'Consumer',
            email: 'consumer@app.dev',
            points: 100,
        );

        return new class(34, 'http://127.0.0.1', $app, $user) extends Context {
            public function getOperation(): ?object
            {
                return new class {
                    public function getUsability(): int
                    {
                        return 1;
                    }
                };
            }
        };
    }
}
