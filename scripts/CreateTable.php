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
        'Namespace-Ttl-Index' => [
            'type' => 'global',
            'keys' => [
                ['name' => 'Namespace', 'types' => ['key' => 'HASH', 'attribute' => 'S']],
                ['name' => 'Ttl', 'types' => ['key' => 'RANGE', 'attribute' => 'N']],
            ],
            'capacity' => ['read' => 5, 'write' => 5]
        ],
    ]
];

$db = new Bego\Database(
    new Aws\DynamoDb\DynamoDbClient($config['aws']), new Aws\DynamoDb\Marshaler()
);

$db->table(new Ordin\Model($config['table']))->create($spec);