<?php

namespace DaveKoala\RoutesExplorer\Tests\Unit;

use DaveKoala\RoutesExplorer\Http\Middleware\RoutesExplorerSecurity;
use DaveKoala\RoutesExplorer\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoutesExplorerSecurityTest extends TestCase
{
    private RoutesExplorerSecurity $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RoutesExplorerSecurity();
    }

    /** @test */
    public function it_allows_access_in_testing_environment_with_debug_enabled()
    {
        config(['app.env' => 'testing']);
        config(['app.debug' => true]);

        $request = Request::create('/test');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_access_in_production_environment()
    {
        $this->app['env'] = 'production';
        config(['routes-explorer.security.allowed_environments' => ['local', 'testing']]);

        $request = Request::create('/test');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });

        $this->assertFalse($called);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_access_when_debug_is_disabled()
    {
        config(['app.env' => 'testing']);
        config(['app.debug' => false]);
        config(['routes-explorer.security.require_debug' => true]);

        $request = Request::create('/test');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });

        $this->assertFalse($called);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_adds_security_headers_to_allowed_responses()
    {
        config(['app.env' => 'testing']);
        config(['app.debug' => true]);

        $request = Request::create('/test');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }
}