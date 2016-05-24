<?php

namespace GitSync;

class Audit
{

    use SerializableTrait;
    public $datetime;
    public $uid;
    public $from;
    public $to;
    public $event;

    public function __construct($uid, $event, $from, $to)
    {
        $this->uid      = $uid;
        $this->event    = $event;
        $this->from     = $from;
        $this->to       = $to;
        $this->datetime = new \DateTime();
    }

    public function serialize()
    {
        $that           = $this;
        $that->datetime = $this->datetime->format(\DateTime::ISO8601);
        return static::getSerializer()->serialize($that, 'json');
    }

    static public function deserialize(&$data)
    {
        $obj           = static::getSerializer()->deserialize($data,
            \get_called_class(), 'json');
        $obj->datetime = new \DateTime($obj->datetime);
        return $obj;
    }
}