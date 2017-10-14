<?php

namespace Mtahv3\LaravelQueueSnsSqs\Jobs;

use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InvalidPayloadException;
use Illuminate\Queue\Jobs\SqsJob;

class SnsSqsJob extends SqsJob {

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName()
    {
        return $this->payload()['TopicArn'];
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {

        $rawPayload = $this->payload();

        $topic = $this->getTopicFromPayload($rawPayload);

        $message = $this->getDecodedMessageFromPayload($rawPayload);

        $topicClass = $this->getTopicClass($topic, $message);

        $serializedClass = serialize($topicClass);

        $data = [
            'command'=>$serializedClass
        ];

        $class = CallQueuedHandler::class;

        ($this->instance = $this->resolve($class))->call($this, $data);
    }

    protected function getTopicClass($topic, $message)
    {
        $prefix = 'App\\Jobs\\';
        $className = $prefix.$topic;

        return $this->container->make($className, ['data'=>$message]);
    }

    protected function getTopicFromPayload($payload)
    {
        if(isset($payload['TopicArn'])) {
            return last(explode(':', $payload['TopicArn']));
        }else{
            throw new InvalidPayloadException('Payload does not contain \'TopicArn\'');
        }
    }

    protected function getDecodedMessageFromPayload($payload)
    {
        if(isset($payload['Message']))
        {
            return json_decode($payload['Message'], true);
        }
        else
        {
            throw new InvalidPayloadException('Payload does not contain \'Message\'');
        }
    }

}