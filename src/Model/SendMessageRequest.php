<?php


namespace AwsExtended\Model;


use AwsExtended\Exceptions\AttributeAlreadyExistException;

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
    const STRING = 'String';
    const BINARY = 'Binary';
    const NUMBER = 'Number';

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
        $this->messageAttributes = [];
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

    public function addStringMessageAttribute(string $attribute, string $value)
    {
        $this->addAttribute(self::STRING, $attribute, $value);
    }

    public function addBinaryMessageAttribute(string $attribute, string $value)
    {
        $this->addAttribute(self::BINARY, $attribute, $value);
    }

    public function addNumberMessageAttribute(string $attribute, string $value)
    {
        $this->addAttribute(self::NUMBER, $attribute, $value);
    }

    private function addAttribute(string $type, string $attribute, string $value)
    {
        if (array_key_exists($attribute, $this->getMessageAttributes())) {
            throw new AttributeAlreadyExistException($attribute);
        }

        $this->messageAttributes += [
            $attribute => [
                'DataType' => $type,
                'StringValue' => $value
            ]
        ];
    }
}
