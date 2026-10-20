<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\OpdController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OpdSyncOnlyTest extends TestCase
{
    public function test_opd_only_exposes_listing_and_sync_routes(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->getControllerClass() === OpdController::class);

        $this->assertEqualsCanonicalizing(['admin.opd.index', 'admin.opd.sync'], $routes->map->getName()->values()->all());
        $this->assertSame(['GET', 'HEAD'], Route::getRoutes()->getByName('admin.opd.index')->methods());
        $this->assertSame(['POST'], Route::getRoutes()->getByName('admin.opd.sync')->methods());
    }
}
