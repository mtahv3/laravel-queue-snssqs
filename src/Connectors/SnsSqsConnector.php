<?php

namespace Mtahv3\LaravelQueueSnsSqs\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;
use Mtahv3\LaravelQueueSnsSqs\Queue\SnsSqsQueue;

class SnsSqsConnector extends SqsConnector {

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        return new SnsSqsQueue(
            new SqsClient($config),
                $config['queue'],
                $config['prefix'] ?? '',
                $config['routes']
            );
    }
}