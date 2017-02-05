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

use Illuminate\Http\Request;

use Laravel\Passport\Client;

use Laravel\Passport\Http\Controllers\ClientController;
use Laravel\Passport\Passport;

/**
 * Class ClientsController
 * @package EveScout\Seat\OAuth2Server\Http\Controllers\Admin
 */
class ClientsController extends ClientController
{
    private function isVerifyCsrfTokenExceptValid() {
        // Reflect the class and get default properies
        $middlewareReflector = new \ReflectionClass(\App\Http\Middleware\VerifyCsrfToken::class);
        $properties = $middlewareReflector->getDefaultProperties();

        // If except property not found
        if (! isset($properties['except'])) {
            return FALSE;
        }

        // Get the property and check for the route
        $csrfExceptProperty = $properties['except'];
    
        if (! in_array('oauth2/token', $csrfExceptProperty)) {
            return FALSE;
        }

        return TRUE;
    }

    public function index(Request $request)
    {
        // Check Csrf exception settings and display an error prompt.
        if (! $this->isVerifyCsrfTokenExceptValid()) {
            $request->session()->flash('error', 'OAuth2 will not work until you add \'oauth2/token\' to the \'$except\' property in /app/Http/Middleware/VerifyCsrfToken.php');
        }

        $clients = Client::where('user_id', auth()->user()->id)->get();

        return view('oauth2::admin.clients.index', compact('clients'));
    }

    public function show($client) {
        $client = Client::findOrFail($client);
        $availableScopes = Passport::scopes();

        return view('oauth2::admin.clients.show', compact(['client', 'availableScopes']));
    }

    public function store(Request $request)
    {
        $passport = parent::store($request);

        return redirect()->route('oauth2-admin.clients.show', [$passport->id])
            ->with('success', 'New Client Created');
    }

    public function update(Request $request, $clientId)
    {
        $passport = parent::update($request, $clientId);

        return redirect()->back()
            ->with('success', 'Oauth2 Client Updated');
    }

    public function destroy(Request $request, $clientId)
    {
        parent::destroy($request, $clientId);

        return redirect()->back()
            ->with('success', 'Oauth2 Client has been successfully revoked.');
    }
}