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
