<?php

if (count($argv) != 2) {
    echo "Uusage: {$argv[0]} <config_file>\n";
    exit;
}

require_once 'vendor/autoload.php';
$config = require_once $argv[1];

$spec = [
    'types' => [
        'partition' => 'S',
        'sort'      => null
    ],
    'capacity'  => ['read' => 5, 'write' => 5],
    'indexes' => [
        'Unread-Index' => [
            'type' => 'global',
            'keys' => [
                ['name' => 'Unread', 'types' => ['key' => 'HASH', 'attribute' => 'S']],
            ],
            'capacity' => ['read' => 5, 'write' => 5]
        ],
    ]
];

$client = new Aws\DynamoDb\DynamoDbClient($config['aws']);

$db = new Bego\Database(
    $client, new Aws\DynamoDb\Marshaler()
);

//$client->deleteTable(['TableName' => $config['table']]);

$db->table(new Ordin\Model($config['table']))->create($spec);