<?php

namespace App\Providers;

use Inertia\Inertia;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Pagination\UrlWindow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\LengthAwarePaginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Date::use(CarbonImmutable::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Inertia::version(function () {
            return md5_file(public_path('mix-manifest.json'));
        });
        Inertia::share(function () {
            return [
                'app'    => [
                    'name' => Config::get('app.name')
                ],
                'auth'   => [
                    'user' => Auth::user() ? [
                        'id'    => Auth::user()->id,

                        // 'first_name' => Auth::user()->first_name,
                        // 'last_name'  => Auth::user()->last_name,
                        'email' => Auth::user()->email
                        // 'role'       => Auth::user()->role,
                    ] : null
                ],
                'flash'  => [
                    'success' => Session::get('success')
                ],
                'errors' => Session::get('errors') ? Session::get('errors')->getBag('default')->getMessages() : (object) []
            ];
        });
        $this->registerLengthAwarePaginator();
    }

    /**
     * @return mixed
     */
    protected function registerLengthAwarePaginator()
    {
        $this->app->bind(LengthAwarePaginator::class, function ($app, $values) {
            return new class(...array_values($values)) extends LengthAwarePaginator
            {
                public function only(...$attributes)
                {
                    return $this->transform(function ($item) use ($attributes) {
                        return $item->only($attributes);
                    });
                }

                public function transform($callback)
                {
                    $this->items->transform($callback);

                    return $this;
                }

                public function toArray()
                {
                    return [
                        'data'  => $this->items->toArray(),
                        'links' => $this->links()
                    ];
                }

                public function links($view = null, $data = [])
                {
                    $this->appends(Request::all());

                    $window = UrlWindow::make($this);

                    $elements = array_filter([
                        $window['first'],
                        is_array($window['slider']) ? '...' : null,
                        $window['slider'],
                        is_array($window['last']) ? '...' : null,
                        $window['last']
                    ]);

                    return Collection::make($elements)->flatMap(function ($item) {
                        if (is_array($item)) {
                            return Collection::make($item)->map(function ($url, $page) {
                                return [
                                    'url'    => $url,
                                    'label'  => $page,
                                    'active' => $this->currentPage() === $page
                                ];
                            });
                        } else {
                            return [
                                [
                                    'url'    => null,
                                    'label'  => '...',
                                    'active' => false
                                ]
                            ];
                        }
                    })->prepend([
                        'url'    => $this->previousPageUrl(),
                        'label'  => 'Previous',
                        'active' => false
                    ])->push([
                        'url'    => $this->nextPageUrl(),
                        'label'  => 'Next',
                        'active' => false
                    ]);
                }
            };
        });
    }
}
