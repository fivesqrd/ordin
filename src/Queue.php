<?php

namespace Ordin;

use Bego;
use Aws\DynamoDb;

class Queue
{
    protected $_namespace;

    protected $_table;

    const INDEX_NAME = 'Namespace-Ttl-Index';

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

    public function add(Message $message)
    {
        $this->_table->put(
            $message->namespace($this->_namespace)->item()->attributes()
        );

        return $item;
    }

    public function receive($limit)
    {
        $results = $this->_table->query(static::INDEX_NAME)
            ->key($this->_namespace)
            ->limit($limit)
            ->fetch(); 

        $received = [];

        foreach ($results as $item) {

            $attempts = $item->attribute('Attempts');

            $message = new Message($item);

            $message->prepare();

            /* 
             * Using attempts as a version number for optimistic locking.
             * If the number of attempts is not consistent without our value,
             * another observer has beaten us to taking this message
             */

            $conditions = [
                 Bego\Condition::comperator('Attempts', '=', $attempts),
            ];

            /* 
             * Only if the update succeeded will we return the item 
             */

            if ($this->_table->update($message->item(), $conditions)) {
                $received[] = $message;
            }
        }

        return $received;
    }

    public function unread($message)
    {
        $this->_table->update($message->unread()->item());
    }
}
