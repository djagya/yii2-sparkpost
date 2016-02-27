<?php
/**
 * Message class.
 *
 * @copyright Copyright (c) 2016 Danil Zakablukovskii
 * @license http://www.gnu.org/copyleft/gpl.html
 * @package djagya/yii2-sparkpost
 * @author Danil Zakablukovskii <danil.kabluk@gmail.com>
 */

namespace djagya\sparkpost;

use yii\mail\BaseMessage;

/**
 * Message is a representation of an message that will be consumed by Mailer.
 *
 * @see Mailer
 * @author Danil Zakablukovskii <danil.kabluk@gmail.com>
 * @version 0.1
 */
class Message extends BaseMessage
{

    /**
     * Returns the character set of this message.
     * @return string the character set of this message.
     */
    public function getCharset()
    {
        // TODO: Implement getCharset() method.
    }

    /**
     * Sets the character set of this message.
     * @param string $charset character set name.
     * @return $this self reference.
     */
    public function setCharset($charset)
    {
        // TODO: Implement setCharset() method.
    }

    /**
     * Returns the message sender.
     * @return string the sender
     */
    public function getFrom()
    {
        // TODO: Implement getFrom() method.
    }

    /**
     * Sets the message sender.
     * @param string|array $from sender email address.
     * You may pass an array of addresses if this message is from multiple people.
     * You may also specify sender name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     */
    public function setFrom($from)
    {
        // TODO: Implement setFrom() method.
    }

    /**
     * Returns the message recipient(s).
     * @return array the message recipients
     */
    public function getTo()
    {
        // TODO: Implement getTo() method.
    }

    /**
     * Sets the message recipient(s).
     * @param string|array $to receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     */
    public function setTo($to)
    {
        // TODO: Implement setTo() method.
    }

    /**
     * Returns the reply-to address of this message.
     * @return string the reply-to address of this message.
     */
    public function getReplyTo()
    {
        // TODO: Implement getReplyTo() method.
    }

    /**
     * Sets the reply-to address of this message.
     * @param string|array $replyTo the reply-to address.
     * You may pass an array of addresses if this message should be replied to multiple people.
     * You may also specify reply-to name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     */
    public function setReplyTo($replyTo)
    {
        // TODO: Implement setReplyTo() method.
    }

    /**
     * Returns the Cc (additional copy receiver) addresses of this message.
     * @return array the Cc (additional copy receiver) addresses of this message.
     */
    public function getCc()
    {
        // TODO: Implement getCc() method.
    }

    /**
     * Sets the Cc (additional copy receiver) addresses of this message.
     * @param string|array $cc copy receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     */
    public function setCc($cc)
    {
        // TODO: Implement setCc() method.
    }

    /**
     * Returns the Bcc (hidden copy receiver) addresses of this message.
     * @return array the Bcc (hidden copy receiver) addresses of this message.
     */
    public function getBcc()
    {
        // TODO: Implement getBcc() method.
    }

    /**
     * Sets the Bcc (hidden copy receiver) addresses of this message.
     * @param string|array $bcc hidden copy receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     */
    public function setBcc($bcc)
    {
        // TODO: Implement setBcc() method.
    }

    /**
     * Returns the message subject.
     * @return string the message subject
     */
    public function getSubject()
    {
        // TODO: Implement getSubject() method.
    }

    /**
     * Sets the message subject.
     * @param string $subject message subject
     * @return $this self reference.
     */
    public function setSubject($subject)
    {
        // TODO: Implement setSubject() method.
    }

    /**
     * Sets message plain text content.
     * @param string $text message plain text content.
     * @return $this self reference.
     */
    public function setTextBody($text)
    {
        // TODO: Implement setTextBody() method.
    }

    /**
     * Sets message HTML content.
     * @param string $html message HTML content.
     * @return $this self reference.
     */
    public function setHtmlBody($html)
    {
        // TODO: Implement setHtmlBody() method.
    }

    /**
     * Attaches existing file to the email message.
     * @param string $fileName full file name
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return $this self reference.
     */
    public function attach($fileName, array $options = [])
    {
        // TODO: Implement attach() method.
    }

    /**
     * Attach specified content as file for the email message.
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return $this self reference.
     */
    public function attachContent($content, array $options = [])
    {
        // TODO: Implement attachContent() method.
    }

    /**
     * Attach a file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $fileName file name.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     */
    public function embed($fileName, array $options = [])
    {
        // TODO: Implement embed() method.
    }

    /**
     * Attach a content as file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     */
    public function embedContent($content, array $options = [])
    {
        // TODO: Implement embedContent() method.
    }

    /**
     * Returns string representation of this message.
     * @return string the string representation of this message.
     */
    public function toString()
    {
        // TODO: Implement toString() method.
    }

    /**
     * @see Transmission::send()
     * @return array
     */
    public function toArray()
    {
        return [
            'campaign' => '',
            'metadata' => [],
            'substitutionData' => [],
            'description' => '',
            'replyTo' => '',
            'subject' => '',
            'from' => '',
            'html' => '',
            'text' => '',
            'rfc822' => '',
            'customHeaders' => [],
            'recipients' => [],
            'recipientList' => '',
            'template' => '',
            'trackOpens' => false,
            'trackClicks' => false,
            'useDraftTemplate' => false
        ];
    }
}
