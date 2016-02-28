<?php

class MessageTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        new \yii\console\Application(['id' => 'app', 'basePath' => __DIR__]);
    }

    // tests
    public function testMe()
    {
        // test
    }
}
