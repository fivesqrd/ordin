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
        'Namespace-Sequence-Index' => [
            'type' => 'global',
            'keys' => [
                ['name' => 'Namespace', 'types' => ['key' => 'HASH', 'attribute' => 'S']],
                ['name' => 'SequenceId', 'types' => ['key' => 'RANGE', 'attribute' => 'N']],
            ],
            'capacity' => ['read' => 5, 'write' => 5]
        ],
        'Receipt-Sequence-Index' => [
            'type' => 'global',
            'keys' => [
                ['name' => 'Observer', 'types' => ['key' => 'HASH', 'attribute' => 'S']],
                ['name' => 'SequenceId', 'types' => ['key' => 'RANGE', 'attribute' => 'N']],
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