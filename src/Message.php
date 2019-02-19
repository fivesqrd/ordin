<?php

namespace Ordin;

use Bego;

class Message
{
    protected $_item;

    protected $_attemptLimit = 3;

    /* 5 minutes */
    CONST TTL = 300;

    public static function create($topic, $payload)
    {
        return new static(new Bego\Item([
            'Id'        => bin2hex(random_bytes(16)), 
            'Timestamp' => gmdate('c'),
            'Namespace' => '',
            'Topic'     => $topic, 
            'Ttl'       => gmdate('U') + static::TTL,
            'Destroy'   => gmdate('U') + 2592000,
            'Payload'   => $payload,
            'Attempts'  => 0,
            'Status'    => 'unread'
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
        /* Using the timeslot as a TTL for this attempt */
        $this->_item->remove('Ttl');
        $this->_item->set('Observer', gethostname());
        $this->_item->set('Read', gmdate('c'));
        $this->_item->set('Status', 'read');

        /* Keep tabs on how many times we've returned this message */
        $attempts = $this->_item->attribute('Attempts');
        $this->_item->set('Attempts', $attempts + 1);

        return $attempts;
    }

    /**
     * Mark as unread
     */
    public function unread()
    {
        $this->_item->set('Ttl', gmdate('U') + static::TTL);

        return $this;
    }
}
