<?php

use AwsExtended\Exceptions\AttributeAlreadyExistException;
use AwsExtended\Model\SendMessageRequest;

class SendMessageRequestTest extends PHPUnit_Framework_TestCase
{
    const ATTRIBUTE_STRING_NAME = 'ATTRIBUTE_STRING';
    const ATTRIBUTE_BINARY_NAME = 'ATTRIBUTE_BINARY';
    const ATTRIBUTE_NUMBER_NAME = 'ATTRIBUTE_NUMBER';

    public function testSendMassageRequest()
    {
        $sendMessageRequest = new SendMessageRequest('queueUrl', 'test-SendMessageRequest-Body');

        $this->assertEquals('queueUrl', $sendMessageRequest->getQueueUrl());
        $this->assertNotNull('test-SendMessageRequest-Body', $sendMessageRequest->getMessageBody());
    }

    public function testSendMessageRequestWithAddAttributes()
    {
        $sendMessageRequest = new SendMessageRequest('queueUrl', 'test-SendMessageRequest-Body');

        $binaryFile = decbin(32);
        $sendMessageRequest->addStringMessageAttribute(self::ATTRIBUTE_STRING_NAME, "String value of attribute");
        $sendMessageRequest->addBinaryMessageAttribute(self::ATTRIBUTE_BINARY_NAME, $binaryFile);
        $sendMessageRequest->addNumberMessageAttribute(self::ATTRIBUTE_NUMBER_NAME, "9");

        $this->assertEquals([
            self::ATTRIBUTE_STRING_NAME => [
                'DataType' => SendMessageRequest::STRING,
                'StringValue' => "String value of attribute"
            ],
            self::ATTRIBUTE_BINARY_NAME => [
                'DataType' => SendMessageRequest::BINARY,
                'StringValue' => $binaryFile
            ],
            self::ATTRIBUTE_NUMBER_NAME => [
                'DataType' => SendMessageRequest::NUMBER,
                'StringValue' => "9"
            ]
        ],
            $sendMessageRequest->getMessageAttributes());
    }

    public function testSendMessageRequestWithAddAttributeAlreadyExistThrowException()
    {
        $sendMessageRequest = new SendMessageRequest('queueUrl', 'test-SendMessageRequest-Body');
        $sendMessageRequest->addStringMessageAttribute(self::ATTRIBUTE_STRING_NAME, "String value of attribute");

        $this->expectException(AttributeAlreadyExistException::class);

        $sendMessageRequest->addStringMessageAttribute(self::ATTRIBUTE_STRING_NAME, "Other string value of attribute");
    }
}