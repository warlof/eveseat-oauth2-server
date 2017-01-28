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
	'oauth2server' => [
        'name'          => 'OAuth2 Server',
        'label'         => 'oauth2::seat.oauth2_admin',
        'permission'    => 'superuser',
        'icon'          => 'fa-lock',
        'route_segment' => 'oauth2-admin',
        'entries'       => [
                [
                    'name'   => 'Clients',
                    'label'  => 'oauth2::seat.client',
                    'plural' => true,
                    'icon'   => 'fa-list',
                    'route'  => 'oauth2-admin.clients.index'
                ]
            ]
    ]
];
