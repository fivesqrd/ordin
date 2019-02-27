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
        $attempts = 0;

        do {

            $attributes = $event->namespace($this->_namespace)
                ->sequence()
                ->item()
                ->attributes();

            /* Attempt to create a unique timestamped record */

            $result = $this->_table->put(
                $attributes, [Condition::attributeNotExists('Id')]
            );

            /* if not regenerate and try again */

        } while ($result === false && $attempts++ < 10);

        if (!$result) {
            /* No dice, we've given up */
            throw new \Exception(
                'Could not create unique event record'
            );
        }

        return $event;
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

    public function unread($event, $observer)
    {
        $receipt = new Receipt($this->_table, $observer);

        $item = $receipt->fetch($event->attribute('SequenceId'));

        if (!$item) {
            /* Nothing to delete */
            return false;
        }

        return $receipt->remove($item);
    }

    protected function _getLastRead($observer)
    {
        $results = $this->_table->query(static::INDEX_RECEIPT)
            ->key($observer)
            ->reverse()
            ->limit(1)
            ->fetch();

        if ($results->count() == 0) {
            /* You must be new here */
            return $this->_createFirstRead($observer)->attribute('SequenceId');
        }

        return $results->first()->attribute('SequenceId'); 
    }

    protected function _createFirstRead($observer)
    {
        /* Create a dummy event to read */
        $dummy = Event::create('observer.registered', []);

        /* Save the receipt and return the item */ 
        return (new Receipt($this->_table, $observer))->create($dummy->item());
    }
}
