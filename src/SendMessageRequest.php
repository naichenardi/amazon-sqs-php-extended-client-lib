<?php


namespace AwsExtended;


/**
 * Class SendMessageRequest
 * @package AwsExtended
 *
 * @property string $queueUrl
 * @property string $messageBody
 * @property int $delaySeconds
 * @property array $messageAttributes
 * @property string $messageDeduplicationId
 * @property string $messageGroupId
 */
class SendMessageRequest
{
    private $queueUrl;
    private $messageBody;
    private $delaySeconds;
    private $messageAttributes;
    private $messageDeduplicationId;
    private $messageGroupId;

    public function __construct(string $queueUrl, string $messageBody)
    {
        $this->queueUrl = $queueUrl;
        $this->messageBody = $messageBody;
    }

    /**
     * @return string
     */
    public function getQueueUrl(): string
    {
        return $this->queueUrl;
    }

    /**
     * @return string
     */
    public function getMessageBody(): string
    {
        return $this->messageBody;
    }

    /**
     * @return int
     */
    public function getDelaySeconds(): ?int
    {
        return $this->delaySeconds;
    }

    /**
     * @return array
     */
    public function getMessageAttributes(): ?array
    {
        return $this->messageAttributes;
    }

    /**
     * @return string
     */
    public function getMessageDeduplicationId(): ?string
    {
        return $this->messageDeduplicationId;
    }

    /**
     * @return string
     */
    public function getMessageGroupId(): ?string
    {
        return $this->messageGroupId;
    }


    public function withMessageGroupId(string $messageGroupId): SendMessageRequest
    {
        $this->messageGroupId = $messageGroupId;
        return $this;
    }

    public function withMessageDeduplicationId(string $messageDeduplicationId): SendMessageRequest
    {
        $this->messageDeduplicationId = $messageDeduplicationId;
        return $this;
    }

    public function withDelaySeconds(int $delaySeconds): SendMessageRequest
    {
        $this->delaySeconds = $delaySeconds;
        return $this;
    }

    public function withMessageAttributes(array $messageAttributes): SendMessageRequest
    {
        $this->messageAttributes = $messageAttributes;
        return $this;
    }

    public function withQueueUrl(string $queueUrl): SendMessageRequest
    {
        $this->queueUrl = $queueUrl;
        return $this;
    }

    public function withMessageBody(string $messageBody): SendMessageRequest
    {
        $this->messageBody = $messageBody;
        return $this;
    }

}
