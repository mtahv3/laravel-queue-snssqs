<?php

namespace Mtahv3\LaravelQueueSnsSqs;

use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\ServiceProvider;
use Mtahv3\LaravelQueueSnsSqs\Connectors\SnsSqsConnector;

class LaravelQueueSnsSqsServiceProvider extends ServiceProvider {
    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/snssqs.php',
            'queue.connections.snssqs'
        );
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        /** @var \Illuminate\Queue\QueueManager $queue */
        $queue = $this->app['queue'];


        $queue->addConnector('snssqs', function () {
            return new SnsSqsConnector();
        });
    }


}