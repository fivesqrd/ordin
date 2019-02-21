<?php

namespace Ordin;

use Bego;
use Bego\Condition;
use Aws\DynamoDb;

class Queue
{
    protected $_namespace;

    protected $_table;

    const INDEX_QUEUE   = 'Namespace-Sequence-Index';
    const INDEX_RECEIPT = 'Observer-Sequence-Index';

    public static function instance($config, $namespace)
    {
        if (!isset($config['aws']['version'])) {
            $config['aws']['version'] = '2012-08-10';
        }
        
        $db = new Bego\Database(
            new DynamoDb\DynamoDbClient($config['aws']), new \Aws\DynamoDb\Marshaler()
        );

        $table = $db->table(
            new Model($config['table'])
        );

        return new static($table, $namespace);
    }

    public function __construct($table, $namespace)
    {
        $this->_table = $table;
        $this->_namespace = $namespace;
    }

    public function add(Event $event)
    {
        return $this->_table->put(
            $event->namespace($this->_namespace)->item()->attributes()
        );
    }

    public function receive($observer)
    {
        $lastSequenceId = $this->_getLastRead($observer);

        $query = $this->_table
            ->query(static::INDEX_QUEUE)
            ->key($this->_namespace)
            ->condition(Condition::comperator('SequenceId', '>', $lastSequenceId));

        return new Receive(
            $query, new Receipt($this->_table, $observer)
        );
    }

    protected function _getLastRead($observer)
    {
              /* Key=observer sort=Read:Time */
        $results = $this->_table->query(static::INDEX_RECEIPT)
            ->key($observer)
            ->reverse()
            ->limit(1)
            ->fetch();

        if ($results->count() == 0) {
            return 0;
        }

        return $results->first()->attribute('SequenceId'); 
    }
}
