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

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use LucaDegasperi\OAuth2Server\Middleware\CheckAuthCodeRequestMiddleware;
use LucaDegasperi\OAuth2Server\Middleware\OAuthExceptionHandlerMiddleware;
use LucaDegasperi\OAuth2Server\Middleware\OAuthMiddleware;
use LucaDegasperi\OAuth2Server\Storage\FluentStorageServiceProvider;

/**
 * Class OAuth2ServerServiceProvider
 * @package EveScout\Seat\OAuth2Server
 */
class OAuth2ServerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Authorizer', Authorizer::class);

        $this->addRoutes();
        $this->addMiddleware($router);
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
        // Publish our config before we register OAuth2Server can
        $this->publishes([__DIR__.'/Config/oauth2.php' => config_path('oauth2.php')]);

        // Register OAuth2Server service providers
        $this->app->register(FluentStorageServiceProvider::class);
        $this->app->register(\LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider::class);
    
        // Merge sidebar config for nav
        $this->mergeConfigFrom(__DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
    }

    /**
     * Include the routes
     */
    public function addRoutes()
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    /**
     * Include the middleware needed
     *
     * @param $router
     */
    public function addMiddleware(Router $router)
    {
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(OAuthExceptionHandlerMiddleware::class);

        $router->middleware('oauth', OAuthMiddleware::class);
        $router->middleware('check-authorization-params', CheckAuthCodeRequestMiddleware::class);
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