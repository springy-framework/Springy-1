<?php

/**
 * Springy Framework Configuration File.
 *
 * As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *
 * Do not remove this file.
 */
$conf = [
    /*
     * New routing for PSR-4 controllers
     */
    'routing' => [
        /*
         * Default module for controllers.
         *
         * @var string
         */
        'module' => '',

        /*
         * Default namespace for controllers.
         *
         * @var string
         */
        'namespace' => 'App\\Web\\',

        /*
         * Default namespaces by URI segments.
         *
         * @var array
         */
        'segments' => [
            'api' => 'App\\Api',
        ],

        /*
         * Routing configuration by HTTP host.
         *
         * Keys are regular expressions.
         *
         * @var array
         */
        'hosts' => [
            'localhost\.localdomain' => [
                'module' => 'local',
                'namespace' => 'App\\Local\\Web',
                'segments' => [
                    'api' => 'App\\Local\\Api',
                ],
                'template' => ['$admin'],
            ],
            // Command line controllers
            'cmd\.shell' => [
                'module' => '',
                'namespace' => 'App\\Console',
                'segments' => [],
                'template' => [],
            ],
        ],

        /*
         * Page routing.
         *
         * @var array
         */
        'routes' => [
            'App\\Web\\' => [
                'end-of-user-license-agreement' => 'Eula',
            ],
        ],
    ],

    /*
     * Old style regex routing.
     */
    'routes' => [
        'home(\/)*(\?(.*))*' => [
            'segment' => 0,
            'controller' => 'index',
        ],
    ],
    /*
     * Old style redirections.
     */
    'redirects' => [
        '404' => [
            'segments' => [],
            'get' => [],
            'force_rewrite' => false,
            'host' => 'dynamic',
            'type' => 301,
        ],
    ],
    /*
     * Old style controller rounting.
     */
    'prevalidate_controller' => [
        // 'mycontroller'      => ['command' => 301, 'segments' => 2],
        // 'myothercontroller' => ['command' => 404, 'segments' => 2, 'validate' => ['/^[a-z0-9\-]+$/', '/^[0-9]+$/']],
    ],
    /*
     * Old style controller path by host.
     */
    'host_controller_path' => [
        // 'cmd.shell' => ['$command'],
    ],
    'system_root' => '/',
    'register_method_set_common_urls' => null,
    // URLs comuns do site
    'common_urls'                     => [
        'urlAssets' => [['assets'], [], false, 'static', true],
        'urlHome'   => [[]],
        'urlLogin'  => [['login'], [], false, 'secure', true],
        'urlLogout' => [['logout'], [], false, 'secure', true],
    ],
    'redirect_last_slash' => true,
    'force_slash_on_index' => true,
    'ignored_segments' => 0,
    'assets_dir' => 'assets',
];
