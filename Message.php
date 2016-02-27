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

use yii\base\NotSupportedException;
use yii\mail\BaseMessage;

/**
 * Message is a representation of a message that will be consumed by Mailer.
 *
 * Refer to the API reference to see possible values.
 * @link https://developers.sparkpost.com/api/#/reference/transmissions API Reference
 * @see Mailer
 * @author Danil Zakablukovskii <danil.kabluk@gmail.com>
 * @version 0.1
 */
class Message extends BaseMessage
{
    /**
     * Either a string with email address OR an array with 'name' and 'email' keys.
     * @var string|array
     */
    private $_from;

    /**
     * Either a stored recipients list id:
     * [
     *  'list_id' => string,
     * ]
     *
     * OR an array of recipients:
     * [
     *  'address' => string | ['email' => '', 'name' => '', 'header_to' => ''],
     * ],
     * where 'header_to' is used for Cc and Bcc recipients.
     *
     * Refer to the sections "Recipient Attributes" and "Recipient Lists".
     *
     * @link https://developers.sparkpost.com/api/#/reference/recipient-lists Recipient Attributes
     * @link https://developers.sparkpost.com/api/#/reference/recipient-lists Recipient Lists
     * @var array
     */
    private $_to = [];

    /**
     * @var string Email address
     */
    private $_replyTo;

    /**
     * Headers other than "Subject", "From", "To", and "Reply-To":
     * [
     *  'Cc' => string,
     * ]
     * @var array
     */
    private $_headers = [];

    private $_subject;

    private $_text;

    private $_html;

    /**
     * Returns the character set of this message.
     * @return string the character set of this message.
     */
    public function getCharset()
    {
        return null;
    }

    /**
     * Not supported by SparkPost.
     * @param string $charset character set name.
     * @return $this self reference.
     * @throws NotSupportedException
     */
    public function setCharset($charset)
    {
        throw new NotSupportedException('Charset is not supported by SparkPost.');
    }

    /**
     * Returns the message sender.
     * @return string the sender
     */
    public function getFrom()
    {
        return $this->_from;
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
        if (is_string($from)) {
            $this->_from = $from;
        } elseif (is_array($from)) {
            $this->_from = $this->emailsToString($from);
        }

        return $this;
    }

    /**
     * Returns the message recipient(s).
     * @return array the message recipients or the recipients list id
     */
    public function getTo()
    {
        if (isset($this->_to['list_id'])) {
            return [$this->_to['list_id']];
        }

        $addresses = [];
        foreach ($this->_to as $item) {
            // skip recipients with set header_to, i.e. CC and BCC recipients
            if (isset($item['header_to'])) {
                continue;
            }

            if (is_array($item['address'])) {
                $addresses[$item['address']['email']] = $item['address']['name'];
            } else {
                $addresses[] = $item['address'];
            }
        }

        return $addresses;
    }

    /**
     * Sets the message recipient(s).
     *
     * @param string|array $to receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     */
    public function setTo($to)
    {
        $this->addRecipient($to);

        return $this;
    }

    /**
     * Set stored recipients list id to use instead usual $to.
     *
     * @link https://developers.sparkpost.com/api/#/reference/recipient-lists Recipient Lists
     * @param string $listId Stored recipients list id.
     * @return $this
     */
    public function setStoredRecipientsList($listId)
    {
        $this->_to = ['list_id' => $listId];

        return $this;
    }

    /**
     * Returns the reply-to address of this message.
     * @return string the reply-to address of this message.
     */
    public function getReplyTo()
    {
        return $this->_replyTo;
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
        if (is_string($replyTo)) {
            $this->_replyTo = $replyTo;
        } elseif (is_array($replyTo)) {
            $this->_replyTo = $this->emailsToString($replyTo);
        }

        return $this;
    }

    /**
     * Returns the Cc (additional copy receiver) addresses of this message.
     * @return array the Cc (additional copy receiver) addresses of this message.
     */
    public function getCc()
    {
        $addresses = [];
        foreach ($this->_to as $item) {
            if (!isset($item['header_to'])) {
                continue;
            }

            // fixme: better way to find substring
            // if email is not represented in Cc header - it's the Bcc email
            if (!isset($this->_headers['Cc']) || strpos($this->_headers['Cc'], $item['header_to']) === false) {
                continue;
            }

            if (is_array($item['address'])) {
                $addresses[$item['address']['email']] = $item['address']['email'];
            } else {
                $addresses[] = $item['address'];
            }
        }

        return $addresses;
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
        $this->addRecipient($cc, true);

        if (is_string($cc)) {
            $this->_headers['Cc'] = $cc;
        } elseif (is_array($cc)) {
            $this->_headers['Cc'] = $this->emailsToString($cc);
        }

        return $this;
    }

    /**
     * Returns the Bcc (hidden copy receiver) addresses of this message.
     * @return array the Bcc (hidden copy receiver) addresses of this message.
     */
    public function getBcc()
    {
        $addresses = [];
        foreach ($this->_to as $item) {
            if (!isset($item['header_to'])) {
                continue;
            }

            // fixme: better way to find substring
            // if email is represented in the Cc header, it's not the Bcc email
            if (isset($this->_headers['Cc']) && strpos($this->_headers['Cc'], $item['header_to']) !== false) {
                continue;
            }

            if (is_array($item['address'])) {
                $addresses[$item['address']['email']] = $item['address']['email'];
            } else {
                $addresses[] = $item['address'];
            }
        }

        return implode(', ', $addresses);
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
        $this->addRecipient($bcc, true);

        return $this;
    }

    /**
     * Returns the message subject.
     * @return string the message subject
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * Sets the message subject.
     * @param string $subject message subject
     * @return $this self reference.
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;

        return $this;
    }

    /**
     * Sets message plain text content.
     * @param string $text message plain text content.
     * @return $this self reference.
     */
    public function setTextBody($text)
    {
        $this->_text = $text;
    }

    /**
     * Sets message HTML content.
     * @param string $html message HTML content.
     * @return $this self reference.
     */
    public function setHtmlBody($html)
    {
        $this->_html = $html;
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
        return $this->getSubject() . ' - Recipients:'
        . ' [TO] ' . implode('; ', $this->getTo())
        . ' [CC] ' . implode('; ', $this->getCc())
        . ' [BCC] ' . implode('; ', $this->getBcc());
    }

    /**
     * @see Transmission::send()
     * @return array
     */
    public function toArray()
    {
        return [
            'options' => [
                'start_time' => '',
                'open_tracking' => true,
                'click_tracking' => true,
                'transactional' => false,
                'sandbox' => false,
                'skip_suppression' => false,
            ],
            'recipients' => $this->_to,
            'campaign_id' => '', // 64 byte
            'description' => '', // 1024 bytes
            'metadata' => [],
            'substitutionData' => [],
            'return_path' => '', // required
            // 20 Mb
            'content' => [
                // if template
                'template_id' => '', // 64 bytes
                'use_draft_template' => false,
                // if not a template
                'html' => '',
                'text' => '',
                'subject' => '',
                'from' => '',
                'reply_to' => '',
                'headers' => '',
                'attachments' => [
                    'type' => '',
                    'name' => '', // 255 bytes
                    'data' => '',
                ],
                'inline_images' => [
                    'type' => '',
                    'name' => '', // 255 bytes
                    'data' => '',
                ],
            ],
        ];
    }

    /**
     * Processes given emails and fill recipients field
     * @param array|string $emails
     * @param bool $copy adds header_to field with a placeholder to make the item a CC/BCC copy
     */
    protected function addRecipient($emails, $copy = false)
    {
        if (is_string($emails)) {
            $emails = [$emails];
        }

        foreach ($emails as $email => $name) {
            $address = [];

            if (is_int($email)) {
                $address['email'] = $name;
            } else {
                $address = [
                    'name' => $name,
                    'email' => $email,
                ];
            }

            if ($copy) {
                $address['header_to'] = '%mainRecipient%';
            }

            $this->_to[] = ['address' => $address];
        }
    }

    /**
     * Converts emails array to the string: ['name' => 'email'] -> '"name" <email>'
     * @param array $emails
     * @return string
     */
    private function emailsToString($emails)
    {
        $addresses = [];
        foreach ($emails as $email => $name) {
            if (is_int($email)) {
                $addresses[] = $name;
            } else {
                $addresses[] = "\"{$name}\" <{$email}>";
            }
        }

        return implode(',', $addresses);
    }
}
