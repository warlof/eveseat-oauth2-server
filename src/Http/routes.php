<?php

/*
 * This file is part of OAuth 2.0 Server SeAT Add-on.
 *
 * Copyright (c) 2016 Johnny Splunk <johnnysplunk@eve-scout.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Route::group([
    'namespace' => 'EveScout\Seat\OAuth2Server\Http\Controllers',
], function () {

    Route::group(['prefix' => 'oauth2', 'as' => 'oauth2.'], function() {
        Route::group(['middleware' => ['check-authorization-params', 'auth']], function() {
            Route::get('/character-chooser', [
                'as'         => 'character-chooser.get',
                'uses'       => 'OAuth2ServerController@getCharacterChooser'
            ]);

            Route::post('/character-chooser', [
                'as'         => 'character-chooser.post',
                'uses'       => 'OAuth2ServerController@postCharacterChooser'
            ]);
        });

        Route::post('/token', [
            'as'         => 'token.post',
            'middleware' => [],
            'uses'       => 'OAuth2ServerController@postToken'
        ]);

        Route::get('/profile', [
            'as'     => 'profile.get',
            'middleware' => ['oauth:character.profile'],
            'uses'   => 'OAuth2ServerController@getProfile'
        ]);
    });

    Route::group([
        'middleware' => ['auth:api']
    ], function(){
        Route::get('/api/v2/characters/{character_id}', [
            'as' => 'oauth2.character',
            'uses' => 'OAuth2ServerController@getCharacter'
        ]);
    });

    Route::group([
        'namespace'  => 'Admin',
        'middleware' => ['web', 'auth'],
        'prefix'     => 'oauth2-admin'
    ], function () {

        Route::resource('clients', 'ClientsController', ['as' => 'oauth2-admin']);

        Route::post('/clients/token/{oauth_token}', [
            'as'    => 'oauth2-admin.token.revoke',
            'uses'  => 'TokensController@revoke'
        ]);
    });

});