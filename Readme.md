# Laravel Queue Driver For SNS

In utilizing a fan-out pattern with SNS and SQS the default Laravel
 Queue system fails as it expects a structured method from the queue
 that's been serialized by Laravel itself. 
 
This queue driver will allow you to take raw JSON data from an SQS
queue that was received from an SNS subscription and map it to
the correct job handler in Laravel.

## Installation

You can install the package through [Composer](http://getcomposer.org/)
with the following command

```bash
composer require mtahv3/laravel-queue-snssqs
```

### Service Provider

The service provider should register with Laravel automatically 
through a composer hook [Info](https://laravel.com/docs/5.5/packages#package-discovery).
 
If this causes issues and you want to manually register the service provider
manually by adding the following line to the `providers` array
 in your `config/app.php` file.

```php
Mtahv3\LaravelQueueSnsSqs\LaravelQueueSnsSqsServiceProvider::class
```


## Configuration

To configure the driver you need to add the following element to
the `connections` array in `config/queue.php`

```php
'snssqs' => [
    'driver' => 'snssqs',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'prefix' => env('AWS_SQS_QUEUE_PREFIX'),
    'queue' => env('AWS_SQS_QUEUE_NAME'),
    'region' => env('AWS_REGION'),
    'routes'=> [
        '*TopicName'=>'App\\Jobs\\JobName',
        'AnotherName*'=>'App\\Jobs\\AnotherJob'
    ]
]
```

### Routes

Messages off the queue are mapped by their SNS topic name. You will need
to modify the `routes` element of the array you added previously to 
map a SNS Topic Name to a Job.

Note: You can use wildcards (*) in the topic name if you want to 
ignore suffixes or prefixes in the Topic Name. For example
if you prefix `prod` and `test` to your topic names, you could
write one route using wildcard to map both prod and test with a
single line.





