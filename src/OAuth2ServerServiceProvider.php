<?php

/*
 * This file is part of OAuth 2.0 Server SeAT Add-on.
 *
 * Copyright (c) 2016 Johnny Splunk <johnnysplunk@eve-scout.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EveScout\Seat\OAuth2Server;

use Carbon\Carbon;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Routing\Router;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;
use Laravel\Passport\Http\Middleware\CheckScopes;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;

/**
 * Class OAuth2ServerServiceProvider
 * @package EveScout\Seat\OAuth2Server
 */
class OAuth2ServerServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy'
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->addRoutes();
        $this->addMiddlewares($router);
        $this->setupOAuth();
        $this->addViews();
        $this->addTranslations();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/oauth2.php', 'oauth2');

        // Register OAuth2Server service providers
        $this->app->register(PassportServiceProvider::class);

        $this->registerPolicies();
    
        // Merge sidebar config for nav
        $this->mergeConfigFrom(__DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
    }

    /**
     * Include the routes
     */
    public function addRoutes()
    {
        Passport::routes();

        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    public function addMiddlewares(Router $router)
    {
        //$router->pushMiddlewareToGroup()
        $router->middleware('scopes', CheckScopes::class);
        $router->middleware('scope', CheckForAnyScope::class);
    }

    public function setupOAuth()
    {
        Passport::tokensExpireIn(Carbon::now()->addSeconds(config('oauth2.access_token_ttl')));

        Passport::refreshTokensExpireIn(Carbon::now()->addSeconds(config('oauth2.refresh_token_ttl')));

        Passport::tokensCan(config('oauth2.scopes'));
    }

    /**
     * Set the path and namespace for the views
     */
    public function addViews()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'oauth2');
    }

    /**
     * Add the packages translation files
     */
    public function addTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'oauth2');
    }
}