<?php

namespace Mtahv3\LaravelQueueSnsSqs\Queue;


use Illuminate\Queue\SqsQueue;
use Mtahv3\LaravelQueueSnsSqs\Jobs\SnsSqsJob;

class SnsSqsQueue extends SqsQueue {


    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue = $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (count($response['Messages']) > 0) {
            return new SnsSqsJob(
                $this->container, $this->sqs, $response['Messages'][0],
                $this->connectionName, $queue
            );
        }
    }
}