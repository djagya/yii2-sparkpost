<?php

use djagya\sparkpost\Mailer;

class MailerTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _before()
    {
        new \yii\console\Application(['id' => 'app', 'basePath' => __DIR__]);
    }

    public function testApiKeyRequired()
    {
        $this->expectException('\yii\base\InvalidConfigException');
        new Mailer(['useDefaultEmail' => false]);
    }

    public function testApiKeyIsString()
    {
        $this->expectException('\yii\base\InvalidConfigException');
        new Mailer(['apiKey' => [], 'useDefaultEmail' => false]);
    }

    public function testDefaultEmailAdminEmailRequired()
    {
        $this->expectException('\yii\base\InvalidConfigException');
        new Mailer(['apiKey' => 'key']);
    }

    public function testNoDefaultEmail()
    {
        $mailer = new Mailer(['apiKey' => 'key', 'useDefaultEmail' => false]);

        $this->assertEmpty($mailer->defaultEmail);
        $message = $mailer->compose();
        $this->assertEmpty($message->getReplyTo());
    }

    public function testDefaultEmail()
    {
        Yii::$app->params['adminEmail'] = 'test@mail.com';

        $mailer = new Mailer(['apiKey' => 'key']);

        $email = Yii::$app->name . '<' . Yii::$app->params['adminEmail'] . '>';

        $this->assertEquals($mailer->defaultEmail, $email);
        $message = $mailer->compose();
        $this->assertEquals($message->getReplyTo(), $email);
        $this->assertEquals($message->getFrom(), $email);
    }

    public function testSandboxInheritance()
    {
        $mailer = new Mailer(['apiKey' => 'key', 'useDefaultEmail' => false, 'sandbox' => true]);

        $this->assertTrue($mailer->sandbox);
        $message = $mailer->compose();
        $this->assertTrue($message->getSandbox());
    }

    public function testSuccessSend()
    {
        $transmissionSuccess = \Codeception\Util\Stub::make('\SparkPost\Transmission', [
            'send' => function ($messageArray) {
                return ['total_accepted_recipients' => 1, 'total_rejected_recipients' => 0];
            }
        ]);

        $mailer = new Mailer(['apiKey' => 'key', 'useDefaultEmail' => false, 'sandbox' => true]);

        $mailer->getSparkPost()->transmission = $transmissionSuccess;

        $this->assertTrue($mailer->compose()->setTo('mail@example.com')->send());
    }

    public function testRejectedSend()
    {
        $transmissionSuccess = \Codeception\Util\Stub::make('\SparkPost\Transmission', [
            'send' => function ($messageArray) {
                return ['total_accepted_recipients' => 1, 'total_rejected_recipients' => 1];
            }
        ]);

        $mailer = new Mailer(['apiKey' => 'key', 'useDefaultEmail' => false, 'sandbox' => true]);

        $mailer->getSparkPost()->transmission = $transmissionSuccess;

        $this->assertTrue($mailer->compose()->setTo('mail@example.com')->send());
    }

    public function testAllRejectedSend()
    {
        $transmissionSuccess = \Codeception\Util\Stub::make('\SparkPost\Transmission', [
            'send' => function ($messageArray) {
                return ['total_accepted_recipients' => 0, 'total_rejected_recipients' => 1];
            }
        ]);

        $mailer = new Mailer(['apiKey' => 'key', 'useDefaultEmail' => false, 'sandbox' => true]);

        $mailer->getSparkPost()->transmission = $transmissionSuccess;

        $this->assertFalse($mailer->compose()->setTo('mail@example.com')->send());
    }

    public function testRealSend()
    {
        if (!getenv('APIKEY')) {
            $this->markTestSkipped('To test real message sending set "APIKEY" env variable with your real API key from SparkPost');
        }

        $mailer = new Mailer(['apiKey' => getenv('APIKEY'), 'useDefaultEmail' => false, 'sandbox' => true]);

        $this->assertTrue(
            $mailer->compose()
                ->setTextBody('test message')
                ->setSubject('test')
                ->setFrom('test@sparkpostbox.com')
                ->setTo('test@example.com')
                ->send()
        );
    }

    /**
     * We shouldn't get any exceptions here
     */
    public function testEmptySend()
    {
        $mailer = new Mailer(['apiKey' => 'key', 'useDefaultEmail' => false, 'sandbox' => true]);

        $this->assertFalse(
            $mailer->compose()
                ->setTextBody('test message')
                ->setSubject('test')
                ->setFrom('test@sparkpostbox.com')
                ->setTo([])
                ->send()
        );
    }

    public function testDevelopmentMode()
    {
        $transmission = \Codeception\Util\Stub::make('\SparkPost\Transmission', [
            'send' => function ($messageArray) {
                throw new \SparkPost\APIResponseException();
            }
        ]);

        // development mode off
        $mailer = new Mailer([
            'apiKey' => 'key',
            'useDefaultEmail' => false,
            'sandbox' => true,
            'developmentMode' => false
        ]);
        $mailer->getSparkPost()->transmission = $transmission;

        $this->assertFalse($mailer->compose()->setTo('mail@example.com')->send());

        // development mode on - we should get an exception
        $mailer->developmentMode = true;
        $this->expectException(\SparkPost\APIResponseException::class);
        $mailer->compose()->setTo('mail@example.com')->send();
    }

    public function testHttpAdapterConfig()
    {
        $mailer = new Mailer(['apiKey' => 'key', 'useDefaultEmail' => false]);
        $this->assertInstanceOf(\Ivory\HttpAdapter\CurlHttpAdapter::class, $mailer->getSparkPost()->httpAdapter);

        $mailer = new Mailer([
            'apiKey' => 'key',
            'useDefaultEmail' => false,
            'httpAdapter' => \Ivory\HttpAdapter\SocketHttpAdapter::class
        ]);
        $this->assertInstanceOf(\Ivory\HttpAdapter\SocketHttpAdapter::class, $mailer->getSparkPost()->httpAdapter);

        $mailer = new Mailer([
                'apiKey' => 'key',
                'useDefaultEmail' => false,
                'httpAdapter' => function () {
                    return new \Ivory\HttpAdapter\Guzzle6HttpAdapter();
                }
            ]
        );
        $this->assertInstanceOf(\Ivory\HttpAdapter\Guzzle6HttpAdapter::class, $mailer->getSparkPost()->httpAdapter);

        $mailer = new Mailer([
            'apiKey' => 'key',
            'useDefaultEmail' => false,
            'httpAdapter' => [
                'class' => \Ivory\HttpAdapter\RequestsHttpAdapter::class,
            ]
        ]);
        $this->assertInstanceOf(\Ivory\HttpAdapter\RequestsHttpAdapter::class, $mailer->getSparkPost()->httpAdapter);
    }
}
