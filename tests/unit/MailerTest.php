<?php

use djagya\sparkpost\Mailer;

class MailerTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testApiKeyRequired()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException');
        new Mailer();
    }

    public function testApiKeyIsString()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException');
        new Mailer(['apiKey' => []]);
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
        $mailer = new Mailer(['apiKey' => 'key']);

        Yii::$app->params['adminEmail'] = 'test@mail.com';

        $email = Yii::$app->name . '<' . Yii::$app->params['adminEmail'] . '>';

        $this->assertEquals($mailer->defaultEmail, $email);
        $message = $mailer->compose();
        $this->assertEquals($message->getReplyTo(), $email);
    }

    public function testSandboxInheritance()
    {
        $mailer = new Mailer(['apiKey' => 'key', 'sandbox' => true]);

        $this->assertTrue($mailer->sandbox);
        $message = $mailer->compose();
        $this->assertTrue($message->getSandbox());
    }
}
