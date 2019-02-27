<?php

namespace Ordin;

use Bego;

class Event
{
    protected $_item;

    protected $_attemptLimit = 3;

    public static function create($topic, $payload)
    {
        return new static(new Bego\Item([
            'Timestamp'  => gmdate('c'),
            'Topic'      => $topic, 
            'Destroy'    => gmdate('U') + 2592000,
            'Payload'    => $payload,
        ]));
    }

    public function __construct($item)
    {
        $this->_item = $item;
    }

    public function id()
    {
        return $this->get('Id');
    }

    public function topic()
    {
        return $this->get('Topic');
    }

    public function payload($key = null)
    {
        if ($key === null) {
            return $this->get('Payload');
        }

        $data = $this->get('Payload');

        $keys = explode('.', $key);

        foreach ($keys as $innerKey) {
            if (!array_key_exists($innerKey, $data)) {
                return null;
            }

            $data = $data[$innerKey];
        }

        return $data;
    }

    public function sequence()
    {
        $sequenceId = microtime(true);
        $this->_item->set('Id', 'Event:' . $sequenceId);
        $this->_item->set('SequenceId', $sequenceId);

        return $this;
    }

    /**
     * Set this message's queue
     */
    public function namespace($value)
    {
        $this->_item->set('Namespace', $value);

        return $this;
    }

    public function item()
    {
        return $this->_item;
    }

    public function get($key)
    {
        return $this->_item->attribute($key);
    }
}
