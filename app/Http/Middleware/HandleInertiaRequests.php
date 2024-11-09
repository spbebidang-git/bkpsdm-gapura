<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => [
                    'name' => $request->user() ? $request->user()->name : null,
                    'email' => $request->user() ? $request->user()->email : null,
                    'profile_picture' => $request->user() ? $request->user()->profile_picture : null,
                ],
            ],
            'site' => [
                'logo' => get_setting_meta('site_logo') ? asset('assets/media/images/managements/settings/' . get_setting_meta('site_logo')) : asset('assets/media/images/static/site_logo.png')
            ],
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'danger' => fn() => $request->session()->get('danger')
            ],
        ];
    }
}
