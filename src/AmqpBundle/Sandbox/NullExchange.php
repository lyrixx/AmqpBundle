<?php

namespace M6Web\Bundle\AmqpBundle\Sandbox;
use AMQPChannel;

/**
 * Exchange that does not publish anything
 */
class NullExchange extends \AMQPExchange
{
    /**
     * {@inheritdoc}
     */
    public function __construct(AMQPChannel $amqp_channel)
    {
        //noop
    }

    /**
     * {@inheritdoc}
     */
    public function publish(
        $message,
        $routingKey = null,
        $flags = AMQP_NOPARAM,
        array $attributes = array()
    )
    {
        //noop
    }

    /**
     * {@inheritdoc}
     */
    public function declareExchange()
    {
        return true;
    }
}
