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
        $this->_query->filter(Condition::in('Topic', $values));
    }

    public function fetch($limit)
    {
        $results = $this->_query->limit($limit)->fetch(); 

        $received = [];

        foreach ($results as $item) {

            $receipt = $this->_receipt->create($item);

            if (!$receipt) {
                continue;
            }

            /* 
             * Only if the update succeeded will we return the item 
             */

            $received[] = new Event($item);
        }

        return $received;
    }
}