<?php

namespace Ordin;

use Bego;
use Bego\Condition;
use Aws\DynamoDb;

class Channel
{
    protected $_namespace;

    protected $_table;

    protected $_observers;

    public static function instance($config, $namespace, $observer = null)
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

        return new static($table, $namespace, $observer);
    }

    public function __construct($table, $namespace, $observers = [])
    {
        $this->_table = $table;
        $this->_namespace = $namespace;
        $this->_observers = $observers;
    }

    public function watch($observer, $topics = [])
    {
        $this->_observers[$observer] = $topics;
    }

    public function broadcast(Message $message)
    {
        /* Todo add bulk write here */
        foreach ($this->_observers as $observer => $topics) {

            if (!$this->_isInWatchlist($message->get('Topic'), $topics)) {
                continue;
            }
            
            $item = $message->namespace($this->_namespace)->observer($observer)->item();

            $this->_table->put($item->attributes());
        }

        return $item;
    }

    protected function _isInWatchlist($topic, $whitelist) 
    {
        if (empty($whitelist)) {
            /* No specifics, so is watching all topics */
            return true;
        }

        if (in_array($topics, $whitelist)) {
            return true;
        }

        return false;
    }
}
