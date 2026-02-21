<?php
return [
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\\Awz\\Belpost\\Api\\Controller' => 'api'
            ]
        ],
        'readonly' => true
    ],
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'awzbelpost-user',
                    'provider' => [
                        'moduleId' => 'awz.belpost',
                        'className' => '\\Awz\\Belpost\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'awzbelpost-group',
                    'provider' => [
                        'moduleId' => 'awz.belpost',
                        'className' => '\\Awz\\Belpost\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ]
];