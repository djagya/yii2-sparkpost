<?php
/**
 * Mailer class.
 *
 * @copyright Copyright (c) 2016 Danil Zakablukovskii
 * @license http://www.gnu.org/copyleft/gpl.html
 * @package djagya/yii2-sparkpost
 * @author Danil Zakablukovskii <danil.kabluk@gmail.com>
 */

namespace djagya\sparkpost;

use GuzzleHttp\Client;
use Ivory\HttpAdapter\Guzzle6HttpAdapter;
use SparkPost\APIResponseException;
use SparkPost\SparkPost;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\mail\BaseMailer;

/**
 * Mailer consumes Message object and sends it through Sparkpost API.
 *
 * @see Message
 * @author Danil Zakablukovskii <danil.kabluk@gmail.com>
 * @version 0.1
 */
class Mailer extends BaseMailer
{
    const LOG_CATEGORY = 'sparkpost-mailer';

    /**
     * SparkPost API Key, required.
     * @var string
     */
    private $apiKey;

    /**
     * Additional SparkPost config
     * @see SparkPost::$apiDefaults
     * @var array
     */
    public $sparkpostConfig = [];

    public $messageClass = 'djagya\sparkpost\Message';

    /** @var SparkPost */
    private $_sparkPost;

    public function init()
    {
        if (!$this->apiKey) {
            throw new InvalidConfigException('"' . get_class($this) . '::apiKey" must be set.');
        }

        if (!is_string($this->apiKey)) {
            throw new InvalidConfigException('"' . get_class($this) . '::apiKey" must be a string, ' .
                gettype($this->apiKey) . ' given.');
        }

        $this->sparkpostConfig['key'] = $this->apiKey;

        $httpAdapter = new Guzzle6HttpAdapter(new Client());
        $this->_sparkPost = new SparkPost($httpAdapter, $this->sparkpostConfig);
    }

    public function compose($view = null, array $params = [])
    {
        return parent::compose($view, $params);
    }

    /**
     * Refer to https://support.sparkpost.com/customer/en/portal/articles/2140916-extended-error-codes
     * to see detailed error descriptions.
     *
     * @param Message $message
     * @return bool|void
     * @throws \Exception
     */
    protected function sendMessage($message)
    {
        try {
            $result = $this->_sparkPost->transmission->send($message->toArray());

            if (ArrayHelper::getValue($result, 'total_accepted_recipients') === 0) {
                \Yii::info('Transmission #' . ArrayHelper::getValue($result, 'id') . ' was rejected: ' .
                    ArrayHelper::getValue($result, 'total_rejected_recipients') . ' rejected',
                    self::LOG_CATEGORY);

                return false;
            }

            return true;
        } catch (APIResponseException $e) {
            \Yii::error($e->getMessage(), self::LOG_CATEGORY);
            throw new \Exception('An error occurred in mailer, check your application logs.', 500, $e);
        }
    }
}
