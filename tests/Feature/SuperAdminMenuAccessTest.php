<?php

namespace Tests\Feature;

use App\Http\Middleware\IsAdminMiddleware;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SuperAdminMenuAccessTest extends TestCase
{
    public function test_sensitive_management_routes_require_super_admin_even_with_permissions(): void
    {
        foreach (['admin.management.permission.index', 'admin.management.permission.store', 'admin.management.log.index', 'admin.management.log.download'] as $name) {
            $user = Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('hasRole')->with('trainer')->andReturn(false);
            $user->shouldReceive('getRoleNames')->andReturn(collect(['administrator']));
            $user->shouldReceive('hasRole')->with('super_admin')->andReturn(false);
            $user->shouldReceive('can')->andReturn(true);
            $request = Request::create('/admin/management');
            $route = new Route('GET', 'admin/management', fn () => 'ok');
            $route->name($name);
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => $user);

            try {
                app(IsAdminMiddleware::class)->handle($request, fn () => response('unexpected'));
                $this->fail($name.' must reject non-super-admin users.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode(), $name);
            }
        }
    }

    public function test_super_admin_can_access_sensitive_management_routes(): void
    {
        foreach (['admin.management.permission.index', 'admin.management.log.index', 'admin.management.log.download'] as $name) {
            $user = Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('hasRole')->with('trainer')->andReturn(false);
            $user->shouldReceive('getRoleNames')->andReturn(collect(['super_admin']));
            $user->shouldReceive('hasRole')->with('super_admin')->andReturn(true);
            $request = Request::create('/admin/management');
            $route = new Route('GET', 'admin/management', fn () => 'ok');
            $route->name($name);
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => $user);

            $response = app(IsAdminMiddleware::class)->handle($request, fn () => response('allowed'));
            $this->assertSame('allowed', $response->getContent());
        }
    }
}
