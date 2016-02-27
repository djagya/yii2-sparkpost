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
    public $apiKey;

    /**
     * Whether to use the sandbox mode.
     * You can send up to 50 messages.
     * You must set your 'from' address as a sandbox domain.
     * @var bool
     */
    public $sandbox = false;

    /**
     * Additional SparkPost config
     * @see SparkPost::$apiDefaults
     * @var array
     */
    public $sparkpostConfig = [];

    /**
     * Whether to use default email for 'from' and 'reply to' fields if they are empty.
     * @var bool
     */
    public $useDefaultEmail = true;

    /**
     * Default sender email.
     * If not specified, application name + params[adminEmail] will be used.
     * @var string
     */
    public $defaultEmail;

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

        if ($this->useDefaultEmail && !$this->defaultEmail) {
            if (!isset(\Yii::$app->params['adminEmail'])) {
                throw new InvalidConfigException('You must set "' . get_class($this) .
                    '::defaultEmail" or have "adminEmail" key in application params or disable  "' . get_class($this) .
                    '::useDefaultEmail"');
            }

            $this->defaultEmail = \Yii::$app->name . '<' . \Yii::$app->params['adminEmail'] . '>';
        }
    }

    public function compose($view = null, array $params = [])
    {
        /** @var Message $message */
        $message = parent::compose($view, $params);

        if ($this->sandbox) {
            $message->setSandbox(true);
        }

        // set default message sender email
        if ($this->useDefaultEmail) {
            if (!$message->getFrom()) {
                $message->setFrom($this->defaultEmail);
            }
            if (!$message->getReplyTo()) {
                $message->setReplyTo($this->defaultEmail);
            }
        }

        return $message;
    }

    /**
     * Refer to the error codes descriptions to see details.
     *
     * @link https://support.sparkpost.com/customer/en/portal/articles/2140916-extended-error-codes Errors descriptions
     * @param Message $message
     * @return bool
     * @throws \Exception
     */
    protected function sendMessage($message)
    {
        try {
            $result = $this->_sparkPost->transmission->send($message->toSparkPostArray());

            if (ArrayHelper::getValue($result, 'total_accepted_recipients') === 0) {
                \Yii::info('Transmission #' . ArrayHelper::getValue($result, 'id') . ' was rejected: ' .
                    ArrayHelper::getValue($result, 'total_rejected_recipients') . ' rejected',
                    self::LOG_CATEGORY);

                return false;
            }

            if (ArrayHelper::getValue($result, 'total_rejected_recipients') > 0) {
                \Yii::info('Transmission #' . ArrayHelper::getValue($result, 'id') . ': ' .
                    ArrayHelper::getValue($result, 'total_rejected_recipients') . ' rejected',
                    self::LOG_CATEGORY);
            }

            return true;
        } catch (APIResponseException $e) {
            \Yii::error($e->getMessage(), self::LOG_CATEGORY);
            throw new \Exception('An error occurred in mailer, check your application logs.', 500, $e);
        }
    }

    /**
     * @return SparkPost
     */
    public function getSparkPost()
    {
        return $this->_sparkPost;
    }
}
