<?php

namespace GitSync;

trait SerializableTrait
{

    /**
     *
     * @staticvar type $serializer
     * @return \Symfony\Component\Serializer\Serializer
     */
    static public function getSerializer()
    {
        static $serializer = null;
        if (!$serializer) {
            $serializer = new \Symfony\Component\Serializer\Serializer(
                array(
                new \Symfony\Component\Serializer\Normalizer\ObjectNormalizer(),
                new \Symfony\Component\Serializer\Normalizer\PropertyNormalizer(),
                ),
                array(
                new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
                )
            );
        }
        return $serializer;
    }

    abstract public function serialize();

    static public function deserialize(&$data)
    {
        return static::getSerializer()->deserialize($data, \get_called_class(),
                'json');
    }
}