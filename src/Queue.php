<?php

namespace Ordin;

use Bego;
use Bego\Condition;
use Aws\DynamoDb;

class Queue
{
    protected $_table;

    protected $_observer;

    const INDEX_NAME = 'Unread-Index';

    public static function instance($config, $observer)
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

        return new static($table, $observer);
    }

    public function __construct($table, $observer)
    {
        $this->_table = $table;
        $this->_observer = $observer;
    }

    public function receive($limit)
    {
        if (!$this->_observer) {
            throw new \Exception(
                'Observer name not set and cannot be identified'
            );
        }

        /* todo: possbily use stream instead of a query */
        $results = $this->_table->query(static::INDEX_NAME)
            ->key($this->_observer)
            ->limit($limit)
            ->fetch(); 

        $received = [];

        foreach ($results as $item) {

            $message = new Message($item);

            $message->prepare();

            /* 
             * If not unread anymore, don't pass the message
             */

            $conditions = [
                Condition::attributeExists('Unread'),
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
