<?php

namespace Ordin;

use Bego;

class Receipt
{
    protected $_table;

    protected $_observer;

    public function __construct($table, $observer)
    {
        $this->_table = $table;
        $this->_observer = $observer;
    }

    public function create($item)
    {
        $parts = explode(':', $item->attribute('Id'));

        $attributes = [
            'Id'          => "Receipt:{$this->_observer}:{$parts[1]}",
            'SequenceId'  => $item->attribute('SequenceId'),
            'Event'       => $item->attribute('Id'),
            'Observer'    => $this->_observer,
            'Timestamp'   => gmdate('c'),
            'Destroy'     => gmdate('U') + 2592000
        ];

        return $this->_table->put(
            $attributes, [Bego\Condition::attributeNotExists('Id')]
        );
    }
}
