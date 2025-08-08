<?php

/**
 * This file contains the MqttClientWriteToSocketTest class.
 *
 * SPDX-FileCopyrightText: Copyright 2025 Framna Netherlands B.V., Zwolle, The Netherlands
 * SPDX-License-Identifier: MIT
 */

namespace Lunr\Ticks\Mqtt\Tests;

use Lunr\Ticks\AnalyticsDetailLevel;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MessageType;
use RuntimeException;

/**
 * This class contains the write tests for the MqttClient.
 *
 * @covers Lunr\Ticks\Mqtt\MqttClient
 */
class MqttClientWriteToSocketTest extends MqttClientTestCase
{

    /**
     * Set settings on the MqttClient's parent.
     *
     * @return void
     */
    private function setSettings(): void
    {
        $property = $this->reflection->getParentClass()->getProperty('settings');
        $property->setAccessible(TRUE);
        $property->setValue($this->class, $this->settings);
    }

    /**
     * Test that writeToSocket() does not log if trace ID is unavailable.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsWithTraceIDUnavailable(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setSettings();

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn(NULL);

        $this->controller->shouldNotReceive('getSpanId');

        $this->controller->shouldNotReceive('getParentSpanId');

        $this->controller->shouldNotReceive('getSpanSpecifictags');

        $this->controller->shouldNotReceive('stopChildSpan');

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $this->event->expects($this->never())
                    ->method('addTags');

        $this->event->expects($this->never())
                    ->method('addFields');

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->never())
                    ->method('setTraceId');

        $this->event->expects($this->never())
                    ->method('setSpanId');

        $this->event->expects($this->never())
                    ->method('setParentSpanId');

        $this->event->expects($this->never())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Trace ID not available!');

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ 0x01 . 'data', NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() does not log if span ID is unavailable.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsWithSpanIDUnavailable(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setSettings();

        $traceID = '7b333e15-aa78-4957-a402-731aecbb358e';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn(NULL);

        $this->controller->shouldNotReceive('getParentSpanId');

        $this->controller->shouldNotReceive('getSpanSpecifictags');

        $this->controller->shouldNotReceive('stopChildSpan');

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $this->event->expects($this->never())
                    ->method('addTags');

        $this->event->expects($this->never())
                    ->method('addFields');

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->never())
                    ->method('setSpanId');

        $this->event->expects($this->never())
                    ->method('setParentSpanId');

        $this->event->expects($this->never())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Span ID not available!');

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ 0x01 . 'data', NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() does not log if parent span ID is unavailable.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsWithParentSpanIDUnavailable(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setSettings();

        $traceID = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID  = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn(NULL);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->never())
                    ->method('setParentSpanId');

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ 0x01 . 'data', NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs unknown requests at analytics detail level Info.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsUnknownMessageAtInfo(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ 0x01 . 'data', NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs unknown requests at analytics detail level Detailed.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsUnknownMessageAtDetailed(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Detailed);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $string  = 'a3f8d1c9b2e6fa5c04df7e1a29cbb147d58ea6c2f24d2bd5e6d471f8420a3ce1b9b0f3dc1798423e3c7235b984eefc56e471a93d9fe7bc53182c9b3a1ed0d5c';
        $string .= '1f1f89c3db7f2315ea0dbbb7d3d67859fa12b421a378c35ad40fd3f29c48118d9c1a2e1f571e3457bb27c61fb91d3ed1a3c4de918f4beaad8452bc65fd0b983';
        $string .= 'e45c6f3d17a62b0f3e7a0b2d3f16b9180f246e2dcbf2e53db349f10427cbba8a6eb1da94f5c2b3f8a0c5de82f79a14c8d2fb093671f8d6bca3a129dfc384dc0';
        $string .= '7a5e1f419b03705c69ee7fdc9b4e2fc614ce592d5a71ed0f492a72ba68e502d8b1e9c40a73d6c59401f1f8a572dc9346a1cd5b392e89cfad4de9b6a1832efad';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestBody'    => '31' . $string . '30...',
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"UNKNOWN"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ 0x01 . hex2bin($string . '3025e7a4b7d43119'), NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs unknown requests at analytics detail level Full.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsUnknownMessageAtFull(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Full);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $string  = 'a3f8d1c9b2e6fa5c04df7e1a29cbb147d58ea6c2f24d2bd5e6d471f8420a3ce1b9b0f3dc1798423e3c7235b984eefc56e471a93d9fe7bc53182c9b3a1ed0d5c';
        $string .= '1f1f89c3db7f2315ea0dbbb7d3d67859fa12b421a378c35ad40fd3f29c48118d9c1a2e1f571e3457bb27c61fb91d3ed1a3c4de918f4beaad8452bc65fd0b983';
        $string .= 'e45c6f3d17a62b0f3e7a0b2d3f16b9180f246e2dcbf2e53db349f10427cbba8a6eb1da94f5c2b3f8a0c5de82f79a14c8d2fb093671f8d6bca3a129dfc384dc0';
        $string .= '7a5e1f419b03705c69ee7fdc9b4e2fc614ce592d5a71ed0f492a72ba68e502d8b1e9c40a73d6c59401f1f8a572dc9346a1cd5b392e89cfad4de9b6a1832efad';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestBody'    => '31' . $string . '3025e7a4b7d43119',
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"UNKNOWN"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ 0x01 . hex2bin($string . '3025e7a4b7d43119'), NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs connection handshake requests at analytics detail level Info.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsConnectMessageAtInfo(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $input = hex2bin('101900064d51497364700300000a000b');

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ $input, NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs connection handshake requests at analytics detail level Detailed.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsConnectMessageAtDetailed(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Detailed);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $string  = '1019d1c9b2e6fa5c04df7e1a29cbb147d58ea6c2f24d2bd5e6d471f8420a3ce1b9b0f3dc1798423e3c7235b984eefc56e471a93d9fe7bc53182c9b3a1ed0d5c';
        $string .= '1f1f89c3db7f2315ea0dbbb7d3d67859fa12b421a378c35ad40fd3f29c48118d9c1a2e1f571e3457bb27c61fb91d3ed1a3c4de918f4beaad8452bc65fd0b983';
        $string .= 'e45c6f3d17a62b0f3e7a0b2d3f16b9180f246e2dcbf2e53db349f10427cbba8a6eb1da94f5c2b3f8a0c5de82f79a14c8d2fb093671f8d6bca3a129dfc384dc0';
        $string .= '7a5e1f419b03705c69ee7fdc9b4e2fc614ce592d5a71ed0f492a72ba68e502d8b1e9c40a73d6c59401f1f8a572dc9346a1cd5b392e89cfad4de9b6a1832efad';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestBody'    => $string . '3025...',
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"CONNECT"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ hex2bin($string . '3025e7a4b7d43119'), NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs connection handshake requests at analytics detail level Full.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsConnectMessageAtFull(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Full);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $string  = '1019d1c9b2e6fa5c04df7e1a29cbb147d58ea6c2f24d2bd5e6d471f8420a3ce1b9b0f3dc1798423e3c7235b984eefc56e471a93d9fe7bc53182c9b3a1ed0d5c';
        $string .= '1f1f89c3db7f2315ea0dbbb7d3d67859fa12b421a378c35ad40fd3f29c48118d9c1a2e1f571e3457bb27c61fb91d3ed1a3c4de918f4beaad8452bc65fd0b983';
        $string .= 'e45c6f3d17a62b0f3e7a0b2d3f16b9180f246e2dcbf2e53db349f10427cbba8a6eb1da94f5c2b3f8a0c5de82f79a14c8d2fb093671f8d6bca3a129dfc384dc0';
        $string .= '7a5e1f419b03705c69ee7fdc9b4e2fc614ce592d5a71ed0f492a72ba68e502d8b1e9c40a73d6c59401f1f8a572dc9346a1cd5b392e89cfad4de9b6a1832efad';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestBody'    => $string . '3025e7a4b7d43119',
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"CONNECT"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ hex2bin($string . '3025e7a4b7d43119'), NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs disconnection requests at analytics detail level Info.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsDisconnectMessageAtInfo(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $input = chr(0xe0) . chr(0x00);

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ $input, NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs disconnection requests at analytics detail level Detailed.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsDisconnectMessageAtDetailed(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Detailed);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $input = chr(0xe0) . chr(0x00);

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"DISCONNECT"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ $input, NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs disconnection requests at analytics detail level Full.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsDisconnectMessageAtFull(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Full);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $input = chr(0xe0) . chr(0x00);

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willThrowException(new MqttClientException('Unknown message!'));

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"DISCONNECT"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ $input, NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs known requests at analytics detail level Info.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsMessageAtInfo(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willReturn($this->message);

        $this->message->expects($this->once())
                      ->method('getType')
                      ->willReturn(MessageType::PUBLISH());

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ hex2bin('30170008') . 'test/foo' . hex2bin('002a') . 'hello world', NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs known requests at analytics detail level Detailed.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsMessageAtDetailed(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Detailed);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $string  = '1019d1c9b2e6fa5c04df7e1a29cbb147d58ea6c2f24d2bd5e6d471f8420a3ce1b9b0f3dc1798423e3c7235b984eefc56e471a93d9fe7bc53182c9b3a1ed0d5c';
        $string .= '1f1f89c3db7f2315ea0dbbb7d3d67859fa12b421a378c35ad40fd3f29c48118d9c1a2e1f571e3457bb27c61fb91d3ed1a3c4de918f4beaad8452bc65fd0b983';
        $string .= 'e45c6f3d17a62b0f3e7a0b2d3f16b9180f246e2dcbf2e53db349f10427cbba8a6eb1da94f5c2b3f8a0c5de82f79a14c8d2fb093671f8d6bca3a129dfc384dc0';
        $string .= '7a5e1f419b03705c69ee7fdc9b4e2fc614ce592d5a71ed0f492a72ba68e502d8b1e9c40a73d6c59401f1f8a572dc9346a1cd5b392e89cfad4de9b6a1832efad';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willReturn($this->message);

        $this->message->expects($this->once())
                      ->method('getType')
                      ->willReturn(MessageType::PUBLISH());

        $this->message->expects($this->once())
                      ->method('getContent')
                      ->willReturn($string . '3025e7a4b7d43119');

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestBody'    => $string . '3025...',
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"PUBLISH"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ hex2bin('30170008') . 'test/foo' . hex2bin('002a') . 'hello world', NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs known requests at analytics detail level Full.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsMessageAtFull(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Full);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $string  = '1019d1c9b2e6fa5c04df7e1a29cbb147d58ea6c2f24d2bd5e6d471f8420a3ce1b9b0f3dc1798423e3c7235b984eefc56e471a93d9fe7bc53182c9b3a1ed0d5c';
        $string .= '1f1f89c3db7f2315ea0dbbb7d3d67859fa12b421a378c35ad40fd3f29c48118d9c1a2e1f571e3457bb27c61fb91d3ed1a3c4de918f4beaad8452bc65fd0b983';
        $string .= 'e45c6f3d17a62b0f3e7a0b2d3f16b9180f246e2dcbf2e53db349f10427cbba8a6eb1da94f5c2b3f8a0c5de82f79a14c8d2fb093671f8d6bca3a129dfc384dc0';
        $string .= '7a5e1f419b03705c69ee7fdc9b4e2fc614ce592d5a71ed0f492a72ba68e502d8b1e9c40a73d6c59401f1f8a572dc9346a1cd5b392e89cfad4de9b6a1832efad';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willReturn($this->message);

        $this->message->expects($this->once())
                      ->method('getType')
                      ->willReturn(MessageType::PUBLISH());

        $this->message->expects($this->once())
                      ->method('getContent')
                      ->willReturn($string . '3025e7a4b7d43119');

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestBody'    => $string . '3025e7a4b7d43119',
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"PUBLISH"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ hex2bin('30170008') . 'test/foo' . hex2bin('002a') . 'hello world', NULL ]);

        $this->unmockFunction('microtime');
    }

    /**
     * Test that writeToSocket() logs requests with content being NULL.
     *
     * @covers Lunr\Ticks\Mqtt\MqttClient::writeToSocket
     */
    public function testWriteToSocketLogsWithContentNull(): void
    {
        $this->setReflectionPropertyValue('socket', fopen('/dev/null', 'r+'));
        $this->setReflectionPropertyValue('data', [ 'topic' => 'topic' ]);
        $this->setReflectionPropertyValue('level', AnalyticsDetailLevel::Full);
        $this->setSettings();

        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $this->eventLogger->expects($this->once())
                          ->method('newEvent')
                          ->with('outbound_requests_log')
                          ->willReturn($this->event);

        $this->controller->shouldReceive('startChildSpan')
                         ->once();

        $this->controller->shouldReceive('getTraceId')
                         ->once()
                         ->andReturn($traceID);

        $this->controller->shouldReceive('getSpanId')
                         ->once()
                         ->andReturn($spanID);

        $this->controller->shouldReceive('getParentSpanId')
                         ->once()
                         ->andReturn($parentSpanID);

        $this->controller->shouldReceive('getSpanSpecifictags')
                         ->once()
                         ->andReturn([ 'call' => 'controller/method' ]);

        $this->controller->shouldReceive('stopChildSpan')
                         ->once();

        $this->messageProcessor->expects($this->once())
                               ->method('parseAndValidateMessage')
                               ->willReturn($this->message);

        $this->message->expects($this->once())
                      ->method('getType')
                      ->willReturn(MessageType::PUBLISH());

        $this->message->expects($this->once())
                      ->method('getContent')
                      ->willReturn(NULL);

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => 'host',
            'call'   => 'controller/method',
        ];

        $this->event->expects($this->once())
                    ->method('addTags')
                    ->with($tags);

        $fields = [
            'url'            => NULL,
            'startTimestamp' => 1734352683.3516,
            'endTimestamp'   => 1734352683.3516,
            'executionTime'  => 0.0,
            'requestBody'    => NULL,
            'requestHeaders' => '{"qualityOfService":0,"messageId":null,"topic":null,"acknowledgedQualityOfServices":[],"type":"PUBLISH"}',
        ];

        $this->event->expects($this->once())
                    ->method('addFields')
                    ->with($fields);

        $this->event->expects($this->once())
                    ->method('recordTimestamp');

        $this->event->expects($this->once())
                    ->method('setTraceId')
                    ->with($traceID);

        $this->event->expects($this->once())
                    ->method('setSpanId')
                    ->with($spanID);

        $this->event->expects($this->once())
                    ->method('setParentSpanId')
                    ->with($parentSpanID);

        $this->event->expects($this->once())
                    ->method('record');

        $floatval  = 1734352683.3516;
        $stringval = '0.35160200 1734352683';

        $this->mockFunction('microtime', fn(bool $float) => $float ? $floatval : $stringval);

        $method = $this->getReflectionMethod('writeToSocket');
        $method->invokeArgs($this->class, [ hex2bin('30170008') . 'test/foo' . hex2bin('002a') . 'hello world', NULL ]);

        $this->unmockFunction('microtime');
    }

}

?>
