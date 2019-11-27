<?php

namespace Ordin;

use Bego\Condition;

class Fault
{
    protected $_table;

    protected $_observer;

    public function __construct($table, $observer)
    {
        $this->_table = $table;
        $this->_observer = $observer;
    }

    public function create($event, $action, $message)
    {
        $attributes = [
            'Id'          => "Fault:{$this->_observer}:" . uniqid(),
            'Event'       => $event->id(),
            'Observer'    => $this->_observer,
            'Action'      => $action,
            'Message'     => $message,
            'Timestamp'   => gmdate('c'),
        ];

        return $this->_table->put(
            $attributes, [Condition::attributeNotExists('Id')]
        );
    }

    /*
     * Returns a Bego query object
     */
    public function query($start, $stop)
    {
        return $this->_table->query(static::INDEX_FAULT)
            ->key($this->_observer)
            ->condition(Condition::beginsWith('Id', 'Fault:')) //id begins with 'Fault'
            ->condition(Condition::comperator('Timestamp', '>=', $start))
            ->condition(Condition::comperator('Timestamp', '<=', $stop));
    }

    public function fetchById($id)
    {
        return $this->_table->fetch($id);
    }

    public function delete($item)
    {
        return $this->_table->delete($item);
    }
}
