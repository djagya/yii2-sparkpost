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
        $this->assertContains($to, $message->getTo(), 'Unable to set to!');

        $cc = 'ccuser@somedomain.com';
        $message->setCc($cc);
        $this->assertContains($cc, $message->getCc(), 'Unable to set cc!');

        $bcc = 'bccuser@somedomain.com';
        $message->setBcc($bcc);
        $this->assertContains($bcc, $message->getBcc(), 'Unable to set bcc!');

        $text = 'Text email';
        $message->setTextBody($text);

        $html = '<a>Html email</a>';
        $message->setHtmlBody($html);

        $template = 'template-id';
        $message->setTemplateId($template);
        $this->assertEquals($template, $message->getTemplateId());

        $campaign = 'campaign-id';
        $message->setCampaign($campaign);
        $this->assertEquals($campaign, $message->getCampaign());

        $description = 'description';
        $message->setDescription($description);
        $this->assertEquals($description, $message->getDescription());

        $metadata = [
            'key' => 'value',
            'key1' => 'value1',
        ];
        $message->setMetadata($metadata);
        $this->assertEquals($metadata, $message->getMetadata());
    }

    public function testSubstitutionData()
    {
        $message = new Message();

        $data = [
            'key' => 'value',
            'ke1' => 'value',
            'keykey' => [
                '123',
                '456',
                '789',
            ],
        ];
        $message->setSubstitutionData($data);
        $this->assertEquals($data, $message->getSubstitutionData());
    }

    public function testMultipleToAndCcBcc()
    {
        $message = new Message();

        $recipients = [
            'email1@ex.com',
            'email2@ex.com' => 'name',
            'email3@ex.com',
            'email4@ex.com' => 'name1',
        ];

        $message->setTo($recipients);
        $this->assertEquals($recipients, $message->getTo());

        // check if cc, bcc do not affect 'to'
        $cc = 'email1@ex.com';
        $message->setCc($cc);

        $bcc = [
            'bcc@ex.com',
            'bcc1@ex.com' => 'bcc name',
        ];
        $message->setBcc($bcc);

        $this->assertEquals($recipients, $message->getTo());

        $this->assertEquals([$cc], $message->getCc());
        $this->assertEquals($bcc, $message->getBcc());
    }

    public function testRecipientsReset()
    {
        $message = new Message();

        // to
        $recipients = [
            'email1@ex.com',
            'email2@ex.com' => 'name',
            'email3@ex.com',
            'email4@ex.com' => 'name1',
        ];

        $message->setTo($recipients);
        $this->assertEquals($recipients, $message->getTo());

        // reset recipients
        $message->setTo('email1@ex.com');
        $this->assertEquals(['email1@ex.com'], $message->getTo());
        $message->setTo('');
        $this->assertEmpty($message->getTo());


        // cc
        $recipients = [
            'email1@ex.com',
            'email2@ex.com' => 'name',
            'email3@ex.com',
            'email4@ex.com' => 'name1',
        ];

        $message->setCc($recipients);
        $this->assertEquals($recipients, $message->getCc());

        // reset recipients
        $message->setCc('email1@ex.com');
        $this->assertEquals(['email1@ex.com'], $message->getCc());
        $message->setCc('');
        $this->assertEmpty($message->getCc());


        // bcc
        $recipients = [
            'email1@ex.com',
            'email2@ex.com' => 'name',
            'email3@ex.com',
            'email4@ex.com' => 'name1',
        ];

        $message->setBcc($recipients);
        $this->assertEquals($recipients, $message->getBcc());

        // reset recipients
        $message->setBcc('email1@ex.com');
        $this->assertEquals(['email1@ex.com'], $message->getBcc());
        $message->setBcc('');
        $this->assertEmpty($message->getBcc());
    }

    public function testAttachFile()
    {
        $message = new Message();

        $fileName = __FILE__;
        $message->attach($fileName);

        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);
        $attachment = $attachments[0];
        $this->assertEquals($attachment['name'], basename($fileName));
        $this->assertEquals($attachment['type'], \yii\helpers\FileHelper::getMimeType($fileName));
        $this->assertEquals($attachment['data'], base64_encode(file_get_contents($fileName)));

        $message->attach($fileName);
        $attachments = $message->getAttachments();
        $this->assertCount(2, $attachments);
    }

    public function testAttachContent()
    {
        $message = new Message();

        $fileName = 'test.txt';
        $fileContent = 'Test attachment content';
        $message->attachContent($fileContent, ['fileName' => $fileName]);

        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);
        $attachment = $attachments[0];
        $this->assertEquals($attachment['name'], $fileName);
        $this->assertEquals($attachment['data'], base64_encode($fileContent));

        $message->attachContent($fileContent);
        $attachments = $message->getAttachments();
        $this->assertCount(2, $attachments);
        $attachment = $attachments[1];
        $this->assertEquals($attachment['name'], 'file_1');
    }

    public function testEmbedFile()
    {
        $message = new Message();

        $fileName = __DIR__ . '/../test_image.png';

        $cid = $message->embed($fileName);
        $this->assertEquals('image_0', $cid);

        $images = $message->getImages();
        $this->assertCount(1, $images);
        $image = $images[0];
        $this->assertEquals($image['name'], 'test_image.png');
        $this->assertEquals($image['type'], \yii\helpers\FileHelper::getMimeType($fileName));
        $this->assertEquals($image['data'], base64_encode(file_get_contents($fileName)));

        $cid1 = $message->embed($fileName);
        $images = $message->getImages();
        $this->assertEquals('image_1', $cid1);
        $this->assertCount(2, $images);
    }

    public function testEmbedContent()
    {
        $message = new Message();

        $fileName = __DIR__ . '/../test_image.png';

        $cid = $message->embedContent(file_get_contents($fileName), ['fileName' => 'test_image.png']);
        $this->assertEquals('image_0', $cid);

        $images = $message->getImages();
        $this->assertCount(1, $images);
        $image = $images[0];
        $this->assertEquals($image['name'], 'test_image.png');
        $this->assertEquals($image['data'], base64_encode(file_get_contents($fileName)));

        $cid1 = $message->embedContent(file_get_contents($fileName));
        $images = $message->getImages();
        $this->assertCount(2, $images);
        $image = $images[1];
        $this->assertEquals($image['name'], 'image_1');
        $this->assertEquals($cid1, 'image_1');
    }

    public function testUserData()
    {
        $message = new Message();

        // To
        $message->setTo([
            'example@mail.com' => [
                'name' => 'Recipient #1',
                'metadata' => [
                    'key' => 'value',
                ],
                'substitution_data' => [
                    'template_key' => 'value',
                ],
                'tags' => ['tag1', 'tag2'],
            ],
        ]);
        $this->assertEquals(['example@mail.com' => 'Recipient #1'], $message->getTo());
        $this->assertEquals([
            'example@mail.com' => [
                'metadata' => [
                    'key' => 'value',
                ],
                'substitution_data' => [
                    'template_key' => 'value',
                ],
                'tags' => ['tag1', 'tag2'],
            ]
        ], $message->getUserData());

        // Sparkpost array
        $this->assertEquals([
            [
                'address' => ['email' => 'example@mail.com', 'name' => 'Recipient #1'],
                'metadata' => [
                    'key' => 'value',
                ],
                'substitution_data' => [
                    'template_key' => 'value',
                ],
                'tags' => ['tag1', 'tag2'],
            ]
        ], $message->toSparkPostArray()['recipients']);

        // Cc
        $message->setCc([
            'example@mail.com' => [
                'name' => 'Recipient #1',
                'metadata' => [
                    'key' => 'value',
                ],
                'substitution_data' => [
                    'template_key' => 'value',
                ],
                'tags' => ['tag1', 'tag2'],
            ],
        ]);
        $this->assertEquals(['example@mail.com' => 'Recipient #1'], $message->getCc());
        $this->assertEquals([
            'example@mail.com' => [
                'metadata' => [
                    'key' => 'value',
                ],
                'substitution_data' => [
                    'template_key' => 'value',
                ],
                'tags' => ['tag1', 'tag2'],
            ]
        ], $message->getUserData());

        // Bcc
        $message->setBcc([
            'example@mail.com' => [
                'name' => 'Recipient #1',
                'metadata' => [
                    'key' => 'value',
                ],
                'substitution_data' => [
                    'template_key' => 'value',
                ],
                'tags' => ['tag1', 'tag2'],
            ],
        ]);
        $this->assertEquals(['example@mail.com' => 'Recipient #1'], $message->getBcc());
        $this->assertEquals([
            'example@mail.com' => [
                'metadata' => [
                    'key' => 'value',
                ],
                'substitution_data' => [
                    'template_key' => 'value',
                ],
                'tags' => ['tag1', 'tag2'],
            ]
        ], $message->getUserData());
    }
}
