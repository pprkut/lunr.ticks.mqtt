<?php

/**
 * This file contains the MqttClient class.
 *
 * SPDX-FileCopyrightText: Copyright 2025 Framna Netherlands B.V., Zwolle, The Netherlands
 * SPDX-License-Identifier: MIT
 */

namespace Lunr\Ticks\Mqtt;

use Lunr\Ticks\AnalyticsDetailLevel;
use Lunr\Ticks\EventLogging\EventInterface;
use Lunr\Ticks\EventLogging\EventLoggerInterface;
use Lunr\Ticks\TracingControllerInterface;
use Lunr\Ticks\TracingInfoInterface;
use PhpMqtt\Client\Contracts\MessageProcessor;
use PhpMqtt\Client\Contracts\Repository;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\Logger;
use PhpMqtt\Client\Message;
use PhpMqtt\Client\MessageProcessors\Mqtt311MessageProcessor;
use PhpMqtt\Client\MessageProcessors\Mqtt31MessageProcessor;
use PhpMqtt\Client\MqttClient as BaseMqttClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Overrides MqttClient methods so we can inject introspection calls.
 *
 * @phpstan-import-type Tags from EventInterface
 * @phpstan-import-type Fields from EventInterface
 * @phpstan-type TracingInterface TracingControllerInterface&TracingInfoInterface
 */
class MqttClient extends BaseMqttClient
{

    /**
     * Instance of the message processor.
     * @var MessageProcessor
     */
    private MessageProcessor $messageProcessor;

    /**
     * Instance of an EventLogger
     * @var EventLoggerInterface
     */
    private readonly EventLoggerInterface $eventLogger;

    /**
     * Shared instance of a tracing controller
     * @var TracingInterface
     */
    private readonly TracingControllerInterface&TracingInfoInterface $tracingController;

    /**
     * Current profiling level
     * @var AnalyticsDetailLevel
     */
    private AnalyticsDetailLevel $level;

    /**
     * Array of request/response data
     * @var array<string, string>
     */
    protected array $data;

    /**
     * Constructor with MQTT Logger
     *
     * @param EventLoggerInterface $eventLogger       Instance of an event logger
     * @param TracingInterface     $tracingController Instance of a tracing controller
     * @param string               $host              Host to connect to
     * @param int                  $port              Port for connection
     * @param string|NULL          $clientId          Client ID
     * @param string               $protocol          Connection protocol
     * @param Repository|NULL      $repository        Repository
     * @param LoggerInterface|NULL $logger            Logger
     */
    public function __construct(
        EventLoggerInterface $eventLogger,
        TracingControllerInterface&TracingInfoInterface $tracingController,
        string $host,
        int $port = 1883,
        ?string $clientId = NULL,
        string $protocol = self::MQTT_3_1,
        ?Repository $repository = NULL,
        ?LoggerInterface $logger = NULL
    )
    {
        $clientId = $clientId ?? $this->generateRandomClientId();

        parent::__construct($host, $port, $clientId, $protocol, $repository, $logger);

        $logger = new Logger($host, $port, $clientId, $logger);

        switch ($protocol)
        {
            case self::MQTT_3_1_1:
                $this->messageProcessor = new Mqtt311MessageProcessor($clientId, $logger);
                break;
            case self::MQTT_3_1:
            default:
                $this->messageProcessor = new Mqtt31MessageProcessor($clientId, $logger);
                break;
        }

        $this->eventLogger       = $eventLogger;
        $this->tracingController = $tracingController;

        $this->data  = [];
        $this->level = AnalyticsDetailLevel::Info;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        unset($this->data);
        unset($this->level);
        unset($this->messageProcessor);
    }

    /**
     * Set a custom analytics detail level.
     *
     * @param AnalyticsDetailLevel $level The analytics detail level to set
     *
     * @return void
     */
    public function setAnalyticsDetailLevel(AnalyticsDetailLevel $level): void
    {
        $this->level = $level;
    }

    /**
     * Finalize measurement point data and send to InfluxDB.
     *
     * @param Fields $fields Field data
     * @param Tags   $tags   Tag data
     *
     * @return void
     */
    private function record(array $fields, array $tags): void
    {
        $event = $this->eventLogger->newEvent('outbound_requests_log');

        $event->recordTimestamp();
        $event->setTraceId($this->tracingController->getTraceId() ?? throw new RuntimeException('Trace ID not available!'));
        $event->setSpanId($this->tracingController->getSpanId() ?? throw new RuntimeException('Span ID not available!'));

        $parentSpanID = $this->tracingController->getParentSpanId();

        if ($parentSpanID != NULL)
        {
            $event->setParentSpanId($parentSpanID);
        }

        $event->addTags(array_merge($this->tracingController->getSpanSpecificTags(), $tags));
        $event->addFields($fields);
        $event->record();
    }

    /**
     * Prepare data according to loglevel.
     *
     * @param string|null $data Data to prepare for logging.
     *
     * @return string|null $data
     */
    private function prepareLogData(?string $data): ?string
    {
        if (is_null($data))
        {
            return NULL;
        }

        if ($this->level === AnalyticsDetailLevel::Detailed && strlen($data) > 512)
        {
            return substr($data, 0, 512) . '...';
        }

        return $data;
    }

    /**
     * Writes some data to the socket. If a {@see $length} is given, and it is shorter
     * than the data, only {@see $length} amount of bytes will be sent.
     *
     * @param string   $data   Data to write to the socket
     * @param int|null $length The length of the data
     *
     * @return void
     */
    protected function writeToSocket(string $data, ?int $length = NULL): void
    {
        $this->tracingController->startChildSpan();
        $startTimestamp = microtime(TRUE);
        parent::writeToSocket($data, $length);
        $endTimestamp = microtime(TRUE);

        try
        {
            $message = $this->messageProcessor->parseAndValidateMessage($data);
        }
        catch (MqttClientException)
        {
            $message = NULL;
        }

        $tags = [
            'type'   => 'MQTT-request',
            'domain' => $this->getHost(),
        ];

        $fields = [
            'startTimestamp' => $startTimestamp,
            'endTimestamp'   => $endTimestamp,
            'executionTime'  => (float) bcsub((string) $endTimestamp, (string) $startTimestamp, 4),
        ];

        $parsedData = [
            'qualityOfService'              => 0,
            'messageId'                     => NULL,
            'topic'                         => NULL,
            'acknowledgedQualityOfServices' => [],
        ];

        if ($message !== NULL)
        {
            $parsedData['type']                          = $message->getType()->getKey();
            $parsedData['qualityOfService']              = $message->getQualityOfService();
            $parsedData['messageId']                     = $message->getMessageId();
            $parsedData['topic']                         = $message->getTopic();
            $parsedData['acknowledgedQualityOfServices'] = $message->getAcknowledgedQualityOfServices();

            if ($this->level->atLeast(AnalyticsDetailLevel::Detailed))
            {
                $fields['requestBody'] = $this->prepareLogData($message->getContent());
            }
        }
        elseif ((ord($data[0]) >> 4) == 1)
        {
            $parsedData['type'] = 'CONNECT';

            if ($this->level->atLeast(AnalyticsDetailLevel::Detailed))
            {
                $fields['requestBody'] = $this->prepareLogData(bin2hex($data));
            }
        }
        elseif ($data == chr(0xe0) . chr(0x00))
        {
            $parsedData['type'] = 'DISCONNECT';
        }
        else
        {
            $parsedData['type'] = 'UNKNOWN';

            if ($this->level->atLeast(AnalyticsDetailLevel::Detailed))
            {
                $fields['requestBody'] = $this->prepareLogData(bin2hex($data));
            }
        }

        $fields['url'] = $parsedData['topic'];

        if ($this->level->atLeast(AnalyticsDetailLevel::Detailed))
        {
            $fields['requestHeaders'] = json_encode($parsedData);
        }

        $this->record($fields, $tags);

        $this->tracingController->stopChildSpan();
    }

    /**
     * Handles the given message according to its contents.
     *
     * @param Message $message The message to handle
     *
     * @return void
     */
    protected function handleMessage(Message $message): void
    {
        $this->tracingController->startChildSpan();
        $startTimestamp = microtime(TRUE);
        parent::handleMessage($message);
        $endTimestamp = microtime(TRUE);

        $parsedData = [
            'type'                          => $message->getType()->getKey(),
            'qualityOfService'              => $message->getQualityOfService(),
            'messageId'                     => $message->getMessageId(),
            'topic'                         => $message->getTopic(),
            'acknowledgedQualityOfServices' => $message->getAcknowledgedQualityOfServices(),
        ];

        $tags = [
            'type'   => 'MQTT-response',
            'domain' => $this->getHost(),
        ];

        $fields = [
            'url'                           => $parsedData['topic'],
            'startTimestamp'                => $startTimestamp,
            'endTimestamp'                  => $endTimestamp,
            'executionTime'                 => (float) bcsub((string) $endTimestamp, (string) $startTimestamp, 4),
        ];

        if ($this->level->atLeast(AnalyticsDetailLevel::Detailed))
        {
            $fields['responseBody']    = $this->prepareLogData($message->getContent());
            $fields['responseHeaders'] = json_encode($parsedData);
        }

        $this->record($fields, $tags);

        $this->tracingController->stopChildSpan();
    }

}

?>
