SparkPost API Yii2 Mailer
======================
SparkPost API Mailer for Yii2  
[![Latest Stable Version](https://poser.pugx.org/djagya/yii2-sparkpost/v/stable)](https://packagist.org/packages/djagya/yii2-sparkpost) [![Build Status](https://travis-ci.org/djagya/yii2-sparkpost.svg)](https://travis-ci.org/djagya/yii2-sparkpost) [![Total Downloads](https://poser.pugx.org/djagya/yii2-sparkpost/downloads)](https://packagist.org/packages/djagya/yii2-sparkpost) [![License](https://poser.pugx.org/djagya/yii2-sparkpost/license)](https://packagist.org/packages/djagya/yii2-sparkpost)



Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist djagya/yii2-sparkpost "*"
```

or add

```
"djagya/yii2-sparkpost": "*"
```

to the require section of your `composer.json` file.


Set Up
------

To use SparkPost you will need to have a [SparkPost Account](https://www.sparkpost.com/). 

Every account has a **free** quota: **100k emails per month**. 

Once you have an account you will need to create an **API Key**.  
You can create as many API keys as you want, and it's best practice to create one for each website.  

For testing purposes, while you're waiting for domain verification, you can use a sandbox mode, which can be enabled in the extension's config.

Usage
-----

To use this extension, add the following code in your application configuration (default cUrl http adapter will be used):

```php 
return [
    //....
    'components' => [
        'mailer' => [
            'class' => 'djagya\sparkpost\Mailer',
            'apiKey' => 'YOUR_API_KEY',
            'viewPath' => '@common/mail',
            'defaultEmail' => 'sender@example.com', // optional if 'adminEmail' app param is specified or 'useDefaultEmail' is false
            'retryLimit' => 5, // optional
        ],
    ],
];
```

If you want to disable default "from" and "reply to" email address for messages you can set `useDefaultEmail` to `false` in Mailer config, but then you must specify "from" email address for every message you send.

Property `retryLimit` allows to make Mailer relatively failover.
Mailer will try to send a transmission few times if it's getting an exception from Sparkpost, because sometimes Sparkpost API fails.
When transmission send attempts count reaches specified `retryLimit` - last exception we got will be thrown.
To disable that behavior and try to send transmission only once you can set `retryLimit` to `0`.

### Http Adapters

You can use different http adapters: cUrl (default), guzzle, etc. Full list is here: [available adapters](https://github.com/egeloen/ivory-http-adapter/blob/master/doc/adapters.md).  
For detailed information refer to [ivory-http-adapter](https://github.com/egeloen/ivory-http-adapter).

To configure a http adapter that will be used by mailer you must specify `httpAdapter` attribute in the component configuration, you can use string, array or closure (`BaseYii::createObject()` is used to instantiate an adapter):

```php 
return [
    //....
    'components' => [
        'mailer' => [
            'class' => 'djagya\sparkpost\Mailer',
            'apiKey' => 'YOUR_API_KEY',
            'viewPath' => '@common/mail',
            'httpAdapter' => 'Ivory\HttpAdapter\Guzzle6HttpAdapter', // OR array or closure
        ],
    ],
];
```

### Send an email

You can then send an email as follows:

```php
Yii::$app->mailer->compose('contact/html')
    ->setFrom('from@domain.com')
    ->setTo($to)
    ->setSubject($from)
    ->send();
```

After email was sent few properties are filled with last transmission information:

```php
$mailer = Yii::$app->mailer;

$mailer->lastTransmissionId; // string, id of the last transmission
$mailer->lastError; // APIResponseException we got from Sparkpost library with detailed information from the response
$mailer->sentCount; // int, amount of successfuly sent messages
$mailer->rejectedCount; // int, amount of rejected messages
```

### Sandbox mode

To test email sending while your domain is not verified by Sparkpost you can use sandbox mode by setting `sandbox` option to true or use `setSandbox` directly for message.  
Besides that you must set "from" email address to sandbox Sparkpost domain. 

```php 
return [
    //....
    'components' => [
        'mailer' => [
            'class' => 'djagya\sparkpost\Mailer',
            'apiKey' => 'YOUR_API_KEY',
            'sandbox' => true,
            'defaultEmail' => 'test@SPARKPOST_SANDBOX_DOMAIN',
        ],
    ],
];
```

OR

```php 
Yii::$app->mailer->compose('contact/html')
    ->setSandbox(true)
    ->setFrom('test@SPARKPOST_SANDBOX_DOMAIN')
    ->setTo($to)
    ->setSubject($subject)
    ->send();
```

### Templates

Sparkpost templates are supported.  
When you use a template, template's "from" and "subject" params will be used and override specified by you values.

To set message template and it's substitution data (template context) you should either give an array with specified `template` key and template data as a second param:

```php 
Yii::$app->mailer->compose(['template' => 'sparkpost_template_id'], ['template_param' => 'value1', ...])
    ->setTo($to)
    ->send();
```

OR use `setTemplateId()` and `setSubstitutionData()` Message methods:

```php
Yii::$app->mailer->compose()
    ->setTemplateId('sparkpost_template_id')
    ->setSubstitutionData(['template_param' => 'value1', ...])
    ->setTo($to)
    ->send();
```

Besides that you can specify different set of template data, metadata, tags. You can specify these data for `To`, `Cc`, `Bcc` recipients.
To do that you need to pass an array of addresses, where `key` is email and `value` is an array with specified `metadata`, `tags` and/or recipient `name`:

```php
$to = [
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
    // ... other possible addresses
];

Yii::$app->mailer->compose(['template' => 'sparkpost_template_id'], ['template_param' => 'value1', ...])
    ->setTo($to)
    ->send();
```

Unit Testing
------------

You must have Codeception installed to be able to run unit tests.

To run tests:  
```
php /vendor/bin/codecept run
```

If you want to try to send a real message, you should add APIKEY environment variable (it should be a real API key from SparkPost).  
Example:  
```
APIKEY=your_api_key php /vendor/bin/codecept run
```


Logs
----

All mailer log messages are logged by `\Yii::error()`, `\Yii::warning()`, `\Yii::info()` under special category - `sparkpost-mailer`.

License
-------

The code for yii2-sparkpost is distributed under the terms of the MIT license (see [LICENSE](LICENSE)).
