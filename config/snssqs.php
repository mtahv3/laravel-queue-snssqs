<?php

return [
    'driver' => 'snssqs',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'prefix' => env('AWS_SQS_QUEUE_PREFIX'),
    'queue' => env('AWS_SQS_QUEUE_NAME'),
    'region' => env('AWS_REGION'),
    'routes'=> [
        'TopicName'=>'Apps\\Jobs\\Handler',
        'Wildcard*'=>'Apps\\Jobs\\Handler2',
        '*MoreWildcard'=>'Apps\\Jobs\\Handler3'
    ]
];