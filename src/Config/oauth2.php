<?php

/*
 * This file is part of OAuth 2.0 Server SeAT Add-on.
 *
 * (c) Johnny Splunk <johnnysplunk@eve-scout.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Grant Types
    |--------------------------------------------------------------------------
    |
    | Your OAuth2 Server can issue an access token based on different grant
    | types you can even provide your own grant type.
    |
    | To choose which grant type suits your scenario, see
    | http://oauth2.thephpleague.com/authorization-server/which-grant
    |
    | Please see this link to find available grant types
    | http://git.io/vJLAv
    |
    */

    'grant_types' => [
        'authorization_code' => [
            'class' => '\League\OAuth2\Server\Grant\AuthCodeGrant',
            'access_token_ttl' => 3600,
            'auth_token_ttl'   => 3600
        ]
    ],

    'access_token_ttl' => 3600,

    'refresh_token_ttl' => 3600,

    'scopes' => [
        // character.profile scope
        'character.profile' => 'View character public information.',
        // character.roles scope
        'character.roles' => 'View roles assigned to character to grant access to application features.',
        // email scope
        'email' => 'View email address used for the authorization service.',
    ]

];
