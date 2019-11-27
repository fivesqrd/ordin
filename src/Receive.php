<?php

namespace Ordin;

use Bego;
use Bego\Condition;
use Aws\DynamoDb;

class Receive
{
    protected $_query;

    protected $_receipt;

    public function __construct($query, $receipt)
    {
        $this->_query = $query;
        $this->_receipt = $receipt;
    }

    public function topics($values)
    {
        if (empty($values)) {
            throw new \Excption('List of event topics may not be empty');
        }
        
        $this->_query->filter(Condition::in('Topic', $values));

        return $this;
    }

    public function limit($value)
    {
        $this->_query->limit($value);

        return $this;
    }

    public function fetch()
    {
        $results = $this->_query->fetch(null); 

        $received = [];

        foreach ($results as $item) {

            $receipt = $this->_receipt->create($item);

            if (!$receipt) {
                continue;
            }

            /* 
             * Only if the receipt was created will we return the item 
             */

            $received[] = new Event($item);
        }

        return $received;
    }
}
