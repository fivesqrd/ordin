# Ordin
Ordin is a simple message queue library for PHP that uses DynamoDB as backend. The queue is implemented to allow multiple observers to receive the same event, but that each observer will receive an event only once.

This is useful in distributed micro service environments, where an event should be seen by all types of micro services, but only by one instance of each micro service type.

## Configuration
```
$config = [
    'namespace' => 'My-App'
    'table' => 'My-Table-Name',
    'aws' => [
        'version' => '2012-08-10',
        'region'  => 'eu-west-1',
        'credentials' => [
            'key'    => 'my-key',
            'secret' => 'my-secret',
        ],
    ],
];
```

## Preparing a DynamoDb table
Create a local config file say config.php

```
<?php

return [
    'table' => 'My-Table-Name',
    'aws' => [
        'version' => 'latest',
        'region'  => 'eu-west-1',
        'credentials' => [
            'key'    => 'my-key',
            'secret' => 'my-secret',
        ],
    ],
];
```

Run the table create script
```
php vendor/fivesqrd/ordin/scripts/CreateTable.php config.php
```

## Instantiate the queue
```
$queue = Ordin\Queue::instance(
    $config, 'My-App-Ecosystem'
);
```

## Add an event to the queue
```

$event = Ordin\Message::create(
    'order.created', ['to' => 'you@domain.com', 'subject' => 'hello']
);

/* Run as soon as possible */
$result = $queue->add($event);
```

## Get all new messages from a queue
```
/* Receive 5 events */
$messages = $queue->receive($observer, 5);

foreach ($messages as $message) {

    $payload = $message->payload();

    /* Do the work */
    $jobId = $message->id();
}
```