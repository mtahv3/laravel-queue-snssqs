<?php

namespace Mtahv3\LaravelQueueSnsSqs\Jobs;

use Aws\Sqs\SqsClient;
use DateTimeInterface;
use Illuminate\Container\Container;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InvalidPayloadException;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\InteractsWithTime;
use ReflectionClass;

class SnsSqsJob extends SqsJob {

    use InteractsWithTime;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $routes;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  array   $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, SqsClient $sqs, array $job, $connectionName, $queue, $routes)
    {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);

        $this->routes = collect($routes);
        $this->buildJob();
    }

    protected function buildJob()
    {
        $payload = $this->payload();
        $className = $this->findTopicClassFromRoutes($this->getTopicFromPayload($payload));
        $jobClass = (new ReflectionClass($className))->newInstanceWithoutConstructor();
        $payloadBody = [ 
            'uuid' => $payload['MessageId'],
            'maxTries' => $jobClass->tries ?? null,
            'maxExceptions' => $jobClass->maxExceptions ?? null,
            'backoff' => $this->getJobBackoff($jobClass),
            'timeout' => $jobClass->timeout ?? null,
            'retryUntil' => $this->getJobExpiration($jobClass)
        ];

        $payload = array_merge($payloadBody, $payload);

        $this->job['Body'] = json_encode($payload);
    }

    /**
     * Get the backoff for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function getJobBackoff($job)
    {
        if (! method_exists($job, 'backoff') && ! isset($job->backoff)) {
            return;
        }

        return collect($job->backoff ?? $job->backoff())
            ->map(function ($backoff) {
                return $backoff instanceof DateTimeInterface
                                ? $this->secondsUntil($backoff) : $backoff;
            })->implode(',');
    }


    /**
     * Get the expiration timestamp for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function getJobExpiration($job)
    {
        if (! method_exists($job, 'retryUntil') ) {
            return;
        }
        $now = time();
        $expiration = $job->retryUntil();

        $expireTimestamp = $expiration instanceof DateTimeInterface
                        ? $expiration->getTimestamp() : $expiration;
        $timeDifference = abs($expireTimestamp - $now);
        $messageCreatedTime =  (int)($this->job['Attributes']['SentTimestamp'] / 1000);

        return $messageCreatedTime+$timeDifference;
    }

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

        $topicClass = $this->makeTopicClass($topic, $message);

        $serializedClass = serialize($topicClass);

        $data = [
            'command'=>$serializedClass
        ];

        ($this->instance = $this->resolve(CallQueuedHandler::class))->call($this, $data);
    }

    protected function findTopicClassFromRoutes($topic)
    {
        $filtered=$this->routes->filter(function($routeClass, $routeTopic) use ($topic){
            if(fnmatch($routeTopic, $topic)) return true;
        });

        if($filtered->count()){
            $className=$filtered->first();
        }else{
            $className = 'App\\Jobs\\'.$topic;
        }

        return $className;
    }

    protected function makeTopicClass($topic, $message)
    {
        $className = $this->findTopicClassFromRoutes($topic);

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

    public function failed($e)
    {
        $this->markAsFailed();

        $rawPayload = $this->payload();

        $topic = $this->getTopicFromPayload($rawPayload);

        $message = $this->getDecodedMessageFromPayload($rawPayload);

        $topicClass = $this->makeTopicClass($topic, $message);

        if (method_exists($topicClass, 'failed')) {
            $topicClass->failed($e);
        }
    }
}