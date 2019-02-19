# Ordin PHP Client
Ordin is a simple job queue library for PHP that uses DynamoDB for a backend.

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
/* Receive 5 jobs and lock them for 300 seconds (FIFO) */
$messages = $queue->receive(5, 300);

foreach ($messages as $message) {

    $payload = $message->payload();

    /* Do the work */
    $jobId = $message->id();
}
```