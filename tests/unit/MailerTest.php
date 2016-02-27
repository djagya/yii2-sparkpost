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
        $this->setExpectedException('\yii\base\InvalidConfigException');
        new Mailer(['useDefaultEmail' => false]);
    }

    public function testApiKeyIsString()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException');
        new Mailer(['apiKey' => [], 'useDefaultEmail' => false]);
    }

    public function testDefaultEmailAdminEmailRequired()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException');
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
    }

    public function testSandboxInheritance()
    {
        $mailer = new Mailer(['apiKey' => 'key', 'useDefaultEmail' => false, 'sandbox' => true]);

        $this->assertTrue($mailer->sandbox);
        $message = $mailer->compose();
        $this->assertTrue($message->getSandbox());
    }
}
