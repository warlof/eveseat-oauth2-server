<?php

/*
 * This file is part of OAuth 2.0 Server SeAT Add-on.
 *
 * Copyright (c) 2016 Johnny Splunk <johnnysplunk@eve-scout.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EveScout\Seat\OAuth2Server\Http\Controllers\Admin;

use Laravel\Passport\Token;
use Seat\Web\Http\Controllers\Controller;

/**
 * Class ClientsController
 * @package EveScout\Seat\OAuth2Server\Http\Controllers\Admin
 */
class TokensController extends Controller
{
    public function revoke($oauth_token)
    {
        $oauth_token = Token::findOrFail($oauth_token);
        $oauth_token->update(['revoked' => true]);

        return redirect()->back()
            ->with('success', 'Oauth2 Token has been Revoked');
    }
}