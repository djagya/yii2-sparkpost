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

    public $messageClass = 'djagya\sparkpost\Message';

    public function init()
    {
        // todo api key check
    }

    public function compose($view = null, array $params = [])
    {
        return parent::compose($view, $params);
    }

    protected function sendMessage($message)
    {
        \Yii::info('Sending email "' . $message->getSubject() . '" to "' . implode(', ', $message->getTo()) . '"',
            self::LOG_CATEGORY);
    }
}
