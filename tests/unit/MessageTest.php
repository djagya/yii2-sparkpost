<?php

use djagya\sparkpost\Message;

class MessageTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        new \yii\console\Application([
            'id' => 'app',
            'basePath' => __DIR__,
            'components' => [
                'mailer' => new \djagya\sparkpost\Mailer(['apiKey' => 'string', 'useDefaultEmail' => false]),
            ],
        ]);
    }

    public function testCreateMessage()
    {
        $this->assertInstanceOf(Message::class, Yii::$app->mailer->compose());
    }

    public function testUnsupportedCharset()
    {
        $this->setExpectedException('yii\base\NotSupportedException');
        (new Message())->setCharset('charset');
    }

    public function testSetGet()
    {
        $message = new Message();

        $this->assertEmpty($message->getCharset());

        $subject = 'Test Subject';
        $message->setSubject($subject);
        $this->assertEquals($subject, $message->getSubject(), 'Unable to set subject!');

        $from = 'from@somedomain.com';
        $message->setFrom($from);
        $this->assertContains($from, $message->getFrom(), 'Unable to set from!');

        $replyTo = 'reply-to@somedomain.com';
        $message->setReplyTo($replyTo);
        $this->assertContains($replyTo, $message->getReplyTo(), 'Unable to set replyTo!');

        $to = 'someuser@somedomain.com';
        $message->setTo($to);
        $this->assertContains($to, array_keys($message->getTo()), 'Unable to set to!');

        $cc = 'ccuser@somedomain.com';
        $message->setCc($cc);
        $this->assertContains($cc, array_keys($message->getCc()), 'Unable to set cc!');

        $bcc = 'bccuser@somedomain.com';
        $message->setBcc($bcc);
        $this->assertContains($bcc, array_keys($message->getBcc()), 'Unable to set bcc!');
    }
}
