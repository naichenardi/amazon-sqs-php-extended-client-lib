<?php


use Aws\Result;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient as AwsSqsClient;
use AwsExtended\Infrastructure\Config;
use AwsExtended\Interfaces\ConfigInterface;
use AwsExtended\Application\SqsExtendedExtendedClient;
use AwsExtended\Interfaces\SqsExtendedClientInterface;
use AwsExtended\Model\SendMessageRequest;
use Prophecy\Argument;

/**
 * Class SqsClientTest.
 *
 * @package AwsExtended\Application
 *
 * @coversDefaultClass SqsExtendedExtendedClient
 */
class SqsClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SqsExtendedClientInterface
     */
    protected $client;

    /** @var array */
    private $requestArray;

    private $bucketName = 'bucket-name';
    private $queueUrl = 'queue.name';
    private $messageGroupId = 'groupId';

    /**
     * @covers ::sendMessage
     */
    public function testSendMessageNeverSendWithS3()
    {
        $rawMessage = json_encode(['name' => 'Donald Trump', 'country' => 'USA']);

        $messageRequest = new SendMessageRequest($this->queueUrl . 'fifo', $rawMessage);

        $messageRequest
            ->withMessageGroupId($this->messageGroupId)
            ->withMessageDeduplicationId($this->messageGroupId . time());

        $client = $this->getClientMock(new Config(
            [],
            $this->bucketName,
            $this->queueUrl,
            ConfigInterface::NEVER
        ));

        $response = $client->sendMessage($messageRequest);

        $this->assertEquals([
            'QueueUrl' => 'queue.name',
            'MessageBody' => '{"name":"Donald Trump","country":"USA"}',
            'MessageGroupId' => 'groupId',
            'MessageDeduplicationId' => $this->messageGroupId . time()
        ], $response);
    }

    public function testSendMessageIfNeededSendWithS3()
    {
        $rawMessage = json_encode(['name' => 'Donald Trump', 'country' => 'USA']);

        $client = $this->getClientMock(new Config(
            [],
            $this->bucketName,
            $this->queueUrl,
            ConfigInterface::IF_NEEDED
        ));

        $messageRequest = new SendMessageRequest($this->queueUrl, $rawMessage);

        $messageRequest
            ->withMessageGroupId($this->messageGroupId)
            ->withMessageDeduplicationId($this->messageGroupId . time());

        $response = $client->sendMessage($messageRequest);

        $requestToBig = new SendMessageRequest($this->queueUrl, json_encode(range(1, 257 * 1024)));
        $requestToBig->withMessageGroupId($this->messageGroupId)
            ->withMessageDeduplicationId($this->messageGroupId . time());

        $responseToBig = $client->sendMessage($requestToBig);

        $this->assertEquals([
            'QueueUrl' => 'queue.name',
            'MessageBody' => '{"name":"Donald Trump","country":"USA"}',
            'MessageGroupId' => 'groupId',
            'MessageDeduplicationId' => $this->messageGroupId . time()],
            $response);
        $this->assertEquals([
            'QueueUrl' => 'queue.name',
            'MessageBody' => '[[{"Lorem":"bucket-name","Ipsum":"1234-fake-uuid.json"},"fake_object_url"],{"s3BucketName":"bucket-name","s3Key":"1234-fake-uuid.json"}]',
            'MessageGroupId' => 'groupId',
            'MessageDeduplicationId' => $this->messageGroupId . time()],
            $responseToBig);
    }

    /**
     * @covers ::sendMessage
     */
    public function testSendMessageAlwaysSendWithS3AndWithoutMessageGroup()
    {
        $rawMessage = json_encode(['name' => 'Donald Trump', 'country' => 'USA']);

        $client = $this->getClientMock(new Config(
            [],
            $this->bucketName,
            $this->queueUrl,
            ConfigInterface::ALWAYS
        ));

        $messageRequest = new SendMessageRequest($this->queueUrl, $rawMessage);

        $response = $client->sendMessage($messageRequest);

        $this->assertEquals([
            'QueueUrl' => 'queue.name',
            'MessageBody' => '[[{"Lorem":"bucket-name","Ipsum":"1234-fake-uuid.json"},"fake_object_url"],{"s3BucketName":"bucket-name","s3Key":"1234-fake-uuid.json"}]'
        ], $response);
    }

    /**
     * @param ConfigInterface $config
     *   The configuration for the client.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getClientMock(ConfigInterface $config)
    {
        // Mock the AWS clients.
        $client = $this->getMockBuilder(SqsExtendedExtendedClient::class)
            ->setMethods(['getS3Client', 'getSqsClient', 'generateUuid'])
            ->setConstructorArgs([$config])
            ->getMock();

        $client->method('generateUuid')->willReturn('1234-fake-uuid');
        $s3_client = $this->prophesize(S3Client::class);
        $client->method('getS3Client')->willReturn($s3_client->reveal());
        $s3_client->upload(Argument::type('string'), Argument::type('string'), Argument::type('string'))
            ->will(function ($args) {
                return new Result([
                    '@metadata' => ['Lorem' => $args[0], 'Ipsum' => $args[1]],
                    'ObjectUrl' => 'fake_object_url',
                ]);
            });
        $sqs_client = $this->prophesize(AwsSqsClient::class);
        $sqs_client->sendMessage(Argument::type('array'))->willReturnArgument(0);
        $client->method('getSqsClient')->willReturn($sqs_client->reveal());

        return $client;
    }

    /**
     * @covers ::isTooBig
     * @dataProvider isTooBigProvider
     */
    public function testIsTooBig($message, $is_too_big)
    {
        $client = new SqsExtendedExtendedClient(new Config(
            [],
            $this->bucketName,
            $this->queueUrl,
            ConfigInterface::NEVER
        ));
        // Data with more than 2 bytes is considered too big.
        $this->assertSame($is_too_big, $client->isTooBig($message, 2 / 1024));
    }

    /**
     * Test data for the isTooBig test.
     *
     * @return array
     *   The data for the test method.
     */
    public function isTooBigProvider()
    {
        return [
            ['', FALSE],
            [NULL, FALSE],
            ['a', FALSE],
            ['aa', FALSE],
            ['aaa', TRUE],
            [TRUE, FALSE],
            [FALSE, FALSE],
            // Multi byte single characters.
            ['😱', TRUE], // 3 bytes character.
            ['añ', TRUE],  // 2 bytes character.
        ];
    }

}
