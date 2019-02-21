# Ordin
Ordin is a simple event publish/subscribe queue library for PHP that uses DynamoDB as backend. The queue is implemented to allow multiple observers to receive the same event, but that each observer will receive an event only once.

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

$event = Ordin\Event::create(
    'order.created', ['to' => 'you@domain.com', 'subject' => 'hello']
);

/* Add event to queue */
$result = $queue->add($event);
```

## Get all new events from a queue
```
/* Receive 5 events */
$events = $queue->receive($observer)->fetch(5);

foreach ($events as $event) {

    $payload = $event->payload();

    /* Do the work */
    $id = $event->id();
}
```

## Get new events filtered by topic
```
/* Receive 5 events */
$events = $queue->receive($observer)->topics(['order.released'])->fetch(5);

foreach ($events as $event) {

    $payload = $event->payload();

    /* Do the work */
    $id = $event->id();
}
```