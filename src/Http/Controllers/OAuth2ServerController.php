<?php

/*
 * This file is part of OAuth 2.0 Server SeAT Add-on.
 *
 * Copyright (c) 2016 Johnny Splunk <johnnysplunk@eve-scout.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EveScout\Seat\OAuth2Server\Http\Controllers;

use Illuminate\Routing\Controller;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Seat\Services\Image\Eve;
use Seat\Services\Repositories\Character\Character;
use Seat\Services\Repositories\Character\Info;
use Seat\Services\Repositories\Configuration\UserRespository;
use Seat\Eveapi\Models\Eve\AllianceList;
use Seat\Eveapi\Models\Corporation\Title as CorporationTitleModel;
use Seat\Web\Models\Acl\Role;
use EveScout\Seat\OAuth2Server\Models\Session;

/**
 * Class OAuth2ServerController
 * @package EveScout\Seat\OAuth2Server\Http\Controllers
 */
class OAuth2ServerController extends Controller
{
    use UserRespository, Character, Info;

    public function getAuthorize(Request $request)
    {
        // Get auth params
        $authParams = Authorizer::getAuthCodeRequestParams();            

        // Auth params grabs the full client info
        $params = array_except($authParams, 'client');
        $params['client_id'] = $authParams['client']->getId();

        // Get scopes
        $params['scope'] = implode(config('oauth2.scope_delimiter'), array_map(function ($scope) {
            return $scope->getId();
        }, $authParams['scopes']));

        // If a character is not already selected. We should check to see if 
        if (! $request->has('character_id')) {
            $characters = $this->getCharacters();

            // If there is only character on this account then set it.
            if ($characters->count() === 1) {
                $params['character_id'] = $characters->first()->characterID;

            // If there are more than 1 character for this account, show character chooser.
            } elseif (count($characters) > 1) {
                return redirect()->route('oauth2.character-chooser.get', $params);             

            // No characters attached to this acccount. Return error.
            } else {
                $redirectUri = Authorizer::authCodeRequestDeniedRedirectUri();
                return redirect($redirectUri);
            }
        } else {
            $params['character_id'] = $request->input('character_id');
        }

        // Check to see if consent has already been given for this client, endpoint and scopes.
        // Find previous session

        // Get the last session used by this client and owner
        $ownerId = $params['character_id'] . ':' . auth()->user()->id;
        $session = Session::where(['client_id' => $params['client_id'], 'owner_id' => $ownerId])->orderBy('id', 'desc')->first();

        // Previous OAuth2 session found
        if ($session) {
            // Diff the scopes
            $scopes = explode(config('oauth2.scope_delimiter'), $params['scope']);
            $consented_scopes = $session->scopes->pluck('scope_id')->all();

            $scopes_diff = array_diff($scopes, $consented_scopes);

            // If consented to scopes don't ask again just issue authcode
            if (count($scopes_diff) === 0) {
                $redirectUri = Authorizer::issueAuthCode('character', $ownerId, $params);
                return redirect($redirectUri);
            }
        }

        // Send to consent if we are still here
        return view('oauth2::consent', ['params' => $params, 'scopes' => $authParams['scopes'], 'client' => $authParams['client']]);
    }

    public function postAuthorize(Request $request)
    {
        if ($request->input('deny')) {
            $redirectUri = Authorizer::authCodeRequestDeniedRedirectUri();
            return redirect($redirectUri);
        }

        $authParams = Authorizer::getAuthCodeRequestParams();

        $params = array_except($authParams, 'client');
        $params['client_id'] = $authParams['client']->getId();

        $params['character_id'] = $request->input('character_id');
        $ownerId = $params['character_id'] . ':' . auth()->user()->id;

        $redirectUri = Authorizer::issueAuthCode('character', $ownerId, $params);
        return redirect($redirectUri);
    }

    private function getCharacters()
    {
        $characters = $this->getUserCharacters(auth()->user()->id);
        $characters = $characters->unique('characterID');

        return $characters;
    }

    public function getCharacterChooser()
    {
        $authParams = Authorizer::getAuthCodeRequestParams();

        $formParams = array_except($authParams, 'client');
        $formParams['client_id'] = $authParams['client']->getId();
        $formParams['scope'] = implode(config('oauth2.scope_delimiter'), array_map(function ($scope) {
            return $scope->getId();
        }, $authParams['scopes']));

        $characters = $this->getCharacters();

        return view('oauth2::character-chooser', ['params' => $formParams, 'characters' => $characters, 'client' => $authParams['client']]);
    }

    public function postCharacterChooser(Request $request)
    {
        $authParams = Authorizer::getAuthCodeRequestParams();

        $formParams = array_except($authParams, 'client');
        $formParams['client_id'] = $authParams['client']->getId();
        $formParams['scope'] = implode(config('oauth2.scope_delimiter'), array_map(function ($scope) {
            return $scope->getId();
        }, $authParams['scopes']));

        // validate
        $characters = $this->getCharacters();
        $valid_character = $characters->where('characterID', (int) $request->input('character_id'));

        // validation failed
        if (count($valid_character) === 0) {
            return redirect()->route('oauth2.character-chooser.get', $formParams);
        }

        $formParams['character_id'] = $request->input('character_id');

        return redirect()->route('oauth2.authorize.get', $formParams);
    }

    public function postToken()
    {
        return response()->json(Authorizer::issueAccessToken());
    }

    private function getCharacterRoles($character_id)
    {
        $character = $this->getCharacterSheet($character_id);

        // Experimental Title Affiliations
        $characterTitles = $this->getCharacterCorporationTitles($character_id);

        $characterTitlesMap = [];

        foreach ($characterTitles as $title) {
            $corporationTitle = CorporationTitleModel::where(['corporationID' => $character->corporationID, 'titleID' => $title->titleID])->first();
            $characterTitlesMap[] = $corporationTitle->id;
        }

        $characterRoles = Role::whereIn('id', function($q) use ($character, $characterTitlesMap) {
            $q->select('role_id')
              ->from('affiliation_role')
              ->join('affiliations', 'affiliation_role.affiliation_id', '=', 'affiliations.id')
              ->where(['affiliation' => $character->characterID, 'type' => 'char'])
              ->orWhere(['affiliation' => $character->corporationID, 'type' => 'corp'])
              ->orWhere(function ($query) use ($characterTitlesMap) {
                $query->whereIn('affiliation', $characterTitlesMap)
                      ->where(['type' => 'title']);
              });
        })->get();

        return $characterRoles;
    }

    public function getProfile()
    {
        $owner_id = Authorizer::getResourceOwnerId();

        list($character_id, $user_id) = array_pad(explode(':', $owner_id), 2, '');

        $character_info = $this->getCharacterInformation($character_id);
        $account_info = $this->getCharacterAccountInfo($character_id);

        $profile = array();

        $profile['accountCreateDate']   = Carbon::parse($account_info->createDate)->toIso8601String();

        // All accounts are now considered active with alpha clones
        $profile['accountActive']       = true;

        $profile['characterID']         = $character_info->characterID;
        $profile['characterName']       = $character_info->characterName;

        $characterPhoto = new Eve('character', (int) $character_info->characterID, 256, [], false);

        $profile['characterPortrait']   = $characterPhoto->url(256);
        $profile['corporationID']       = $character_info->corporationID;
        $profile['corporationName']     = $character_info->corporationName;
        $profile['allianceID']          = $character_info->allianceID;
        $profile['allianceName']        = $character_info->alliance;
        $profile['allianceTicker']      = null;
        
        if ($character_info->allianceID) {
            $alliance_info = AllianceList::where('allianceID', $character_info->allianceID)->first();

            if ($alliance_info) {
                $profile['allianceTicker'] = $alliance_info->shortName;
            }
        }

        if (Authorizer::hasScope('email')) {
            $profile['email'] = null;

            if (! empty($user_id)) {
                $user = $this->getUser($user_id);

                $profile['email'] = $user->email;
            }
        }

        if (Authorizer::hasScope('character.roles')) {
            $profile['roles'] = $this->getCharacterRoles($character_info->characterID)->pluck('title');
        }

        return response()->json($profile);
    }
}