<?php

namespace AwsExtended\Application;

use Aws\S3\S3Client;
use Aws\Sdk;
use AwsExtended\Infrastructure\S3Pointer;
use AwsExtended\Interfaces\ConfigInterface;
use AwsExtended\Interfaces\SqsExtendedClientInterface;
use AwsExtended\Model\SendMessageRequest;
use Ramsey\Uuid\Uuid;

/**
 * Class SqsClient.
 *
 * @package AwsExtended
 */
class SqsExtendedExtendedClient implements SqsExtendedClientInterface
{
    const RESERVED_ATTRIBUTE_NAME = "SQSLargePayloadSize";
    /**
     * The AWS client to push messages to SQS.
     *
     * @var SqsExtendedExtendedClient
     */
    protected $sqsClient;

    /**
     * The S3 client to interact with AWS.
     *
     * @var S3Client
     */
    protected $s3Client;

    /**
     * The configuration object containing all the options.
     *
     * @var ConfigInterface
     */
    protected $config;

    /**
     * The client factory.
     *
     * @var Sdk
     */
    protected $clientFactory;

    /**
     * SqsClient constructor.
     *
     * @param ConfigInterface $configuration
     *   The configuration object.
     *
     * @throws \InvalidArgumentException if any required options are missing or
     * the service is not supported.
     */
    public function __construct(ConfigInterface $configuration)
    {
        $this->config = $configuration;
    }


    /**
     * {@inheritdoc}
     */
    public function receiveMessage($queue_url = NULL)
    {
        $queue_url = $queue_url ?: $this->config->getSqsUrl();
        // Get the message from the SQS queue.
        $result = $this->getSqsClient()->receiveMessage([
            'QueueUrl' => $queue_url
        ]);
        // Detect if this is an S3 pointer message.
        if (S3Pointer::isS3Pointer($result)) {
            $args = $result->get(1);
            // Get the S3 document with the message and return it.
            return $this->getS3Client()->getObject([
                'Bucket' => $args['s3BucketName'],
                'Key' => $args['s3Key']
            ]);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isTooBig($message, $max_size = NULL)
    {
        // The number of bytes as the number of characters. Notice that we are not
        // using mb_strlen() on purpose.
        $max_size = $max_size ?: static::MAX_SQS_SIZE_KB;
        return strlen($message) > $max_size * 1024;
    }

    /**
     * {@inheritdoc}
     */
    public function getSqsClient()
    {
        if (!$this->sqsClient) {
            $this->sqsClient = $this->getClientFactory()->createSqs();
        }
        return $this->sqsClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getS3Client()
    {
        if (!$this->s3Client) {
            $this->s3Client = $this->getClientFactory()->createS3();
        }
        return $this->s3Client;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Routes all unknown calls to the sqsClient.
     *
     * @param $name
     *   The name of the method to call.
     * @param $arguments
     *   The arguments to use.
     *
     * @return mixed
     *   The return of the call.
     */
    function __call($name, $arguments)
    {
        return call_user_func_array([$this->getSqsClient(), $name], $arguments);
    }

    /**
     * Generate a UUID v4.
     *
     * @return string
     *   The uuid.
     * @throws \Exception
     */
    protected function generateUuid()
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * Initialize and return the SDK client factory.
     *
     * @return Sdk
     *   The client factory.
     */
    protected function getClientFactory()
    {
        if ($this->clientFactory) {
            return $this->clientFactory;
        }
        $this->clientFactory = new Sdk($this->config->getConfig());
        return $this->clientFactory;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function sendMessage(SendMessageRequest $messageRequest)
    {
        $this->sendToS3($messageRequest);

        $messageRequest->withQueueUrl($this->config->getSqsUrl() ?: $messageRequest->getQueueUrl());

        return $this->getSqsClient()->sendMessage($this->formatMessage($messageRequest));
    }

    private function formatMessage(SendMessageRequest $messageRequest): array
    {
        $request = [
            'QueueUrl' => $messageRequest->getQueueUrl(),
            'MessageBody' => $messageRequest->getMessageBody()
        ];
        $request += $messageRequest->getDelaySeconds() ? ['DelaySeconds' => $messageRequest->getDelaySeconds()] : [];
        $request += $messageRequest->getDelaySeconds() ? ['MessageAttributes' => $messageRequest->getMessageAttributes()] : [];

        if ($messageRequest->getMessageGroupId()) {
            $request += [
                'MessageGroupId' => $messageRequest->getMessageGroupId(),
                'MessageDeduplicationId' => $messageRequest->getMessageDeduplicationId()
            ];
        }
        return $request;
    }

    /**
     * @param SendMessageRequest $messageRequest
     * @throws \Exception
     */
    public function sendToS3(SendMessageRequest $messageRequest): void
    {
        switch ($this->config->getSendToS3()) {
            case ConfigInterface::ALWAYS:
                $use_sqs = FALSE;
                break;

            case ConfigInterface::IF_NEEDED:
                $use_sqs = !$this->isTooBig($messageRequest->getMessageBody());
                break;

            default:
                $use_sqs = TRUE;
                break;
        }

        $messageRequest->addNumberMessageAttribute(self::RESERVED_ATTRIBUTE_NAME, mb_strlen($messageRequest->getMessageBody()));

        $use_sqs = $use_sqs || !$this->config->getBucketName();
        if (!$use_sqs) {
            // First send the object to S3. The modify the message to store an S3
            // pointer to the message contents.
            $key = $this->generateUuid() . '.json';
            $receipt = $this->getS3Client()->upload(
                $this->config->getBucketName(),
                $key,
                $messageRequest->getMessageBody()
            );
            // Swap the message for a pointer to the actual message in S3.
            $messageRequest->withMessageBody((string)(new S3Pointer($this->config->getBucketName(), $key, $receipt)));
        }
    }
}
