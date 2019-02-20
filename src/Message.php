<?php

namespace Ordin;

use Bego;

class Message
{
    protected $_item;

    protected $_attemptLimit = 3;

    /* 30 days */
    CONST TTL = 2592000;

    public static function create($topic, $payload)
    {
        return new static(new Bego\Item([
            'Id'        => bin2hex(random_bytes(16)), 
            'Timestamp' => gmdate('c'),
            'Topic'     => $topic, 
            'Destroy'   => gmdate('U') + static::TTL,
            'Payload'   => $payload,
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

    /**
     * Set this message's queue
     */
    public function namespace($value)
    {
        $this->_item->set('Namespace', $value);

        return $this;
    }

    /**
     * Set this message's queue
     */
    public function observer($value)
    {
        $this->_item->set('Observer', $value);
        $this->_item->set('Unread', $value);

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

    /**
     * Prepare for flight
     */
    public function prepare()
    {
        $this->_item->remove('Unread');
        $this->_item->set('Read', gmdate('c'));
    }

    /**
     * Mark as unread
     */
    public function unread($observer)
    {
        $this->_item->set('Unread', $observer);

        return $this;
    }
}
