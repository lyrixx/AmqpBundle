<?php

namespace M6Web\Bundle\AmqpBundle\Tests\Units\Amqp;

use atoum;
use M6Web\Bundle\AmqpBundle\Amqp\Producer as Base;

/**
 * Producer
 */
class Producer extends atoum
{
    public function testConstruct()
    {

        $this
            ->if($exchange = $this->getExchange())
            ->if($exchangeOptions = ['options' => 'test'])
            ->and($producer = new Base($exchange, $exchangeOptions))
                ->object($producer->getExchange())
                    ->isIdenticalTo($exchange)
                ->array($producer->getExchangeOptions())
                    ->hasKey('publish_attributes')
                    ->hasKey('routing_keys')
                    ->hasKey('options')
                    ->contains('test');
    }

    public function testSetOptions()
    {
        $this
            ->if($exchange = $this->getExchange())
            ->and($exchangeOptions = ['publish_attributes' => ['test1'], 'routing_keys' => ['test2']])
            ->and($producer = new Base($exchange, $exchangeOptions))
                ->array($producer->getExchangeOptions())
                    ->isEqualTo($exchangeOptions);
    }

    public function testSendMessagesOk()
    {
        $msgList = [];

        $this
            ->if($msgList = [])
            ->and($exchange = $this->getExchange($msgList))
            ->and($exchangeOptions = [
                'publish_attributes' => ['attr_test' => 'value'],
                'routing_keys' => ['routing_test']
            ])
            ->and($producer = new Base($exchange, $exchangeOptions))
                ->boolean($producer->publishMessage('message1'))
                    ->isTrue()
                ->boolean($producer->publishMessage('message2'))
                    ->isTrue()
                ->array($msgList)
                    ->isEqualTo([
                        ['message1', 'routing_test', AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
                        ['message2', 'routing_test', AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
                ]);
    }

    public function testSendMessagesError()
    {
        $msgList = [];

        $this
            ->if($msgList = [])
            ->and($exchange = $this->getExchange($msgList))
            ->and($exchangeOptions = [
                'publish_attributes' => ['attr_test' => 'value'],
                'routing_keys' => ['routing_test', 'error']
            ])
            ->and($producer = new Base($exchange, $exchangeOptions))
                ->boolean($producer->publishMessage('message1'))
                    ->isTrue()
                ->boolean($producer->publishMessage('error'))
                    ->isFalse()
                ->array($msgList)
                    ->isEqualTo([
                        ['message1', 'routing_test', AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
                        ['message1', 'error', AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
                        ['error', 'routing_test', AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
                    ])
                    ->notContains(['error', 'error', AMQP_NOPARAM, $exchangeOptions['publish_attributes']]);
    }

    public function testSendMessagesWithAttributes()
    {
        $msgList = [];

        // To verify merged attributs
        $this
            ->if($msgList = [])
            ->and($exchange = $this->getExchange($msgList))
            ->and($exchangeOptions = [
                'publish_attributes' => ['attr1' => 'value', 'attr2' => 'value2'],
                'routing_keys' => ['routing_test', 'routing_test2']
            ])
            ->and($msgAttr = ['attr2' => 'overload', 'attr3' => 'value3'])
            ->and($msgAttrMerged = ['attr1' => 'value', 'attr2' => 'overload', 'attr3' => 'value3'])

            ->and($producer = new Base($exchange, $exchangeOptions))
                ->boolean($producer->publishMessage('message1'))
                    ->isTrue()
                ->boolean($producer->publishMessage('message2', AMQP_IMMEDIATE, $msgAttr))
                    ->isTrue()
                ->array($msgList)
                    ->isEqualTo([
                        ['message1', 'routing_test', AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
                        ['message1', 'routing_test2', AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
                        ['message2', 'routing_test', AMQP_IMMEDIATE, $msgAttrMerged],
                        ['message2', 'routing_test2', AMQP_IMMEDIATE, $msgAttrMerged],
                ]);
    }

    public function testSendMessageOk()
    {
        $this
            ->if($exchange = $this->getExchange())
            ->and($exchangeOptions = ['publish_attributes' => ['attr_test'], 'routing_keys' => ['routing_test']])
            ->and($producer = new Base($exchange, $exchangeOptions))
                ->boolean($producer->publishMessage('message1'))
                    ->isTrue()
                ->boolean($producer->publishMessage('message2'))
                    ->isTrue();
    }

    public function testSendMessageWithOverridedRoutingKey()
    {
        $msgList = [];

        // To verify merged attributs
        $this
            ->if($msgList = [])
            ->and($exchange = $this->getExchange($msgList))
            ->and($exchangeOptions = [
                'routing_keys' => ['routing_test', 'routing_test2']
            ])

            ->and($producer = new Base($exchange, $exchangeOptions))
            ->boolean($producer->publishMessage('message1'))
            ->isTrue()
            ->boolean($producer->publishMessage('message2', AMQP_IMMEDIATE, [], ['routing_override1', 'routing_override2']))
            ->isTrue()
            ->array($msgList)
            ->isEqualTo([
                ['message1', 'routing_test', AMQP_NOPARAM, []],
                ['message1', 'routing_test2', AMQP_NOPARAM, []],
                ['message2', 'routing_override1', AMQP_IMMEDIATE, []],
                ['message2', 'routing_override2', AMQP_IMMEDIATE, []],
            ]);
    }

    public function testSendMessagesWithoutRoutingKey()
    {
        $msgList = [];

        $this
            ->if($msgList = [])
            ->and($exchange = $this->getExchange($msgList))
            ->and($exchangeOptions = [
                'publish_attributes' => ['attr_test' => 'value'],
                'routing_keys' => []
            ])
            ->and($producer = new Base($exchange, $exchangeOptions))
                ->boolean($producer->publishMessage('message1'))
                    ->isTrue()
                ->boolean($producer->publishMessage('message2'))
                    ->isTrue()
                ->array($msgList)
                    ->isEqualTo([
                        ['message1', null, AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
                        ['message2', null, AMQP_NOPARAM, $exchangeOptions['publish_attributes']],
            ]);
    }

    protected function getExchange(&$msgList = [])
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();

        $exchange = new \mock\AMQPExchange();

        $exchange->getMockController()->publish = function($message, $routing_key, $flags = AMQP_NOPARAM, array $attributes = []) use (&$msgList) {
            if (($message == 'error') && ($routing_key == 'error')) {
                return false;
            }

            $msgList[] = [$message, $routing_key, $flags , $attributes];

            return true;
        };

        return $exchange;
    }
}
