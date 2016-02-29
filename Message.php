<?php
/**
 * Message class.
 *
 * @copyright Copyright (c) 2016 Danil Zakablukovskii
 * @package djagya/yii2-sparkpost
 * @author Danil Zakablukovskii <danil.kabluk@gmail.com>
 */

namespace djagya\sparkpost;

use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\mail\BaseMessage;

/**
 * Message is a representation of a message that will be consumed by Mailer.
 *
 * Templates are supported and, if used, will override specified content data with template's ones.
 *
 * @link https://developers.sparkpost.com/api/#/reference/transmissions API Reference
 * @see Mailer
 * @author Danil Zakablukovskii <danil.kabluk@gmail.com>
 */
class Message extends BaseMessage
{
    /**
     * Helper map for message composing
     * @var array
     */
    static private $messageAttributesMap = [
        'campaign',
        'metadata',
        'substitutionData',
        'description',
        'returnPath',
        'replyTo',
        'subject',
        'from',
        'html',
        'text',
        'rfc822',
        'customHeaders' => 'headers',
        'recipients' => 'to',
        'recipientList' => 'to.list_id',
        'template' => 'templateId',
        'trackOpens' => 'options.open_tracking',
        'trackClicks' => 'options.click_tracking',
        'useDraftTemplate',

        // TODO wait for sparkpost lib add these values to the mapping
//        'start_time',
//        'sandbox',
//        'attachments',
//        'inline_images' => 'images',
    ];

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
     * where 'header_to' is used to mark Cc and Bcc recipients.
     *
     * Refer to the sections "Recipient Attributes" and "Recipient Lists".
     *
     * @link https://developers.sparkpost.com/api/#/reference/recipient-lists Recipient Lists and Attributes
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
     *  'Cc' => string, // will be set (only if a template isn't used) to mark some recipients as CC recipients
     * ]
     * @var array
     */
    private $_headers = [];

    private $_subject;

    /**
     * If specified - template will be used instead usual text/html body.
     * These fields will not be used for message with specified template:
     * html, text, subject, from, reply_to, headers, attachments, inline_images
     *
     * @var string
     */
    private $_templateId;

    private $_useDraftTemplate = false;

    /**
     * Should be set if html or rfc822 are not set
     * @var string
     */
    private $_text;

    /**
     * Should be set if text or rfc822 are not set
     * @var string
     */
    private $_html;

    /**
     * Should be set if text or html are not set
     * @var string
     */
    private $_rfc822;

    /**
     * Attachments array:
     * [
     *  'type' => string, // MIME type
     *  'name' => string, // 255 bytes
     *  'data' => string, // base64 encoded
     * ]
     * @var array
     */
    private $_attachments = [];

    /**
     * Inline (embed) images array:
     * [
     *  'type' => string, // MIME type
     *  'name' => string, // 255 bytes
     *  'data' => string, // base64 encoded
     * ]
     * @var array
     */
    private $_images = [];

    /**
     * Additional SparkPost message options:
     * [
     *  'start_time' => string, // Format YYYY-MM-DDTHH:MM:SS+-HH:MM or "now". Example: '2015-02-11T08:00:00-04:00'.
     *  'open_tracking' => true,
     *  'click_tracking' => true,
     *  'transactional' => false,
     *  'sandbox' => false,
     *  'skip_suppression' => false,
     * ]
     *
     * Refer to the section "Options Attributes".
     *
     * @link https://developers.sparkpost.com/api/#/reference/transmissions Options Attributes
     * @var array
     */
    private $_options = [];

    /**
     * Campaign ID, 64 bytes max
     * @var string
     */
    private $_campaign;

    /**
     * Description of the Transmission, 1024 bytes max
     * @var string
     */
    private $_description;

    /**
     * 1000 bytes max
     * @var array
     */
    private $_metadata = [];

    /**
     * Substitution data, will be used in message body or template.
     * Key-Value pairs.
     * @var array
     */
    private $_substitutionData = [];

    /**
     * Required only for SparkPost Elite
     * @var string
     */
    private $_returnPath;

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
        if ($this->isListUsed()) {
            return [$this->_to['list_id']];
        }

        $addresses = [];
        foreach ($this->_to as $item) {
            $item = $item['address'];

            // skip recipients with set header_to, i.e. CC and BCC recipients
            if (isset($item['header_to'])) {
                continue;
            }

            if (isset($item['name'])) {
                $addresses[$item['email']] = $item['name'];
            } else {
                $addresses[] = $item['email'];
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
    public function setRecipientsListId($listId)
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
        $this->_replyTo = is_array($replyTo) ?
            $this->emailsToString($replyTo) :
            $replyTo;

        return $this;
    }

    /**
     * Returns the Cc (additional copy receiver) addresses of this message.
     * @return array the Cc (additional copy receiver) addresses of this message.
     */
    public function getCc()
    {
        if ($this->isListUsed()) {
            return [];
        }

        $addresses = [];
        foreach ($this->_to as $item) {
            $item = $item['address'];

            if (!$this->isCopyRecipient($item)) {
                continue;
            }

            if (isset($item['name'])) {
                $addresses[$item['email']] = $item['name'];
            } else {
                $addresses[] = $item['email'];
            }
        }

        return $addresses;
    }

    /**
     * Sets the Cc (additional copy receiver) addresses of this message.
     *
     * Both CC and BCC recipients require set 'header_to' field, it should be the email of the main recipient.
     * SparkPost distinguish CC and BCC recipients by having the same email in 'Cc' header of the message/template.
     *
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
        if ($this->isListUsed()) {
            return [];
        }

        $addresses = [];
        foreach ($this->_to as $item) {
            $item = $item['address'];

            if (!$this->isCopyRecipient($item, true)) {
                continue;
            }

            if (isset($item['name'])) {
                $addresses[$item['email']] = $item['name'];
            } else {
                $addresses[] = $item['email'];
            }
        }

        return $addresses;
    }

    /**
     * Sets the Bcc (hidden copy receiver) addresses of this message.
     *
     * Both CC and BCC recipients require set 'header_to' field, it should be the email of the main recipient.
     * SparkPost distinguish CC and BCC recipients by having the same email in 'Cc' header of the message/template.
     *
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
     * @return string
     */
    public function getTemplateId()
    {
        return $this->_templateId;
    }

    /**
     * @param string $templateId
     * @return $this
     */
    public function setTemplateId($templateId)
    {
        $this->_templateId = $templateId;

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

        return $this;
    }

    /**
     * Sets message HTML content.
     * @param string $html message HTML content.
     * @return $this self reference.
     */
    public function setHtmlBody($html)
    {
        $this->_html = $html;

        return $this;
    }

    /**
     * @return string
     */
    public function getRfc822()
    {
        return $this->_rfc822;
    }

    /**
     * @param string $rfc822
     * @return $this
     */
    public function setRfc822($rfc822)
    {
        $this->_rfc822 = $rfc822;

        return $this;
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
        if (!$fileName) {
            return $this;
        }

        $this->attachContent(file_get_contents($fileName), [
            'fileName' => ArrayHelper::getValue($options, 'fileName', basename($fileName)),
            'contentType' => ArrayHelper::getValue($options, 'contentType', FileHelper::getMimeType($fileName)),
        ]);

        return $this;
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
        if (!$content) {
            return $this;
        }

        $this->_attachments[] = [
            'type' => ArrayHelper::getValue($options, 'contentType', $this->getBinaryMimeType($content)),
            'name' => ArrayHelper::getValue($options, 'fileName', ('file_' . count($this->_attachments))),
            'data' => base64_encode($content),
        ];

        return $this;
    }

    /**
     * Attach a file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $fileName file name.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file and will be used as a CID.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     */
    public function embed($fileName, array $options = [])
    {
        if (!$fileName) {
            return $this;
        }

        $mimeType = FileHelper::getMimeType($fileName);
        if (strpos($mimeType, 'image') === false) {
            throw new \InvalidArgumentException("Only images can be embed. Given file {$fileName} is " . $mimeType);
        }

        $cid = $this->embedContent(file_get_contents($fileName), [
            'fileName' => ArrayHelper::getValue($options, 'fileName', basename($fileName)),
            'contentType' => ArrayHelper::getValue($options, 'contentType', $mimeType),
        ]);

        return $cid;
    }

    /**
     * Attach a content as file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file and will be used as a CID.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     */
    public function embedContent($content, array $options = [])
    {
        if (!$content) {
            return $this;
        }

        $mimeType = $this->getBinaryMimeType($content);
        if (strpos($mimeType, 'image') === false) {
            throw new \InvalidArgumentException("Only images can be embed. Given content is " . $mimeType);
        }

        $cid = 'image_' . count($this->_images);

        $this->_images[] = [
            'type' => ArrayHelper::getValue($options, 'contentType', $mimeType),
            'name' => ArrayHelper::getValue($options, 'fileName', $cid),
            'data' => base64_encode($content),
        ];

        return $cid;
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
     * @return boolean
     */
    public function getSandbox()
    {
        return ArrayHelper::getValue($this->_options, 'sandbox', false);
    }

    /**
     * @param boolean $sandbox
     * @return $this
     */
    public function setSandbox($sandbox)
    {
        $this->_options['sandbox'] = $sandbox;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Possible values:
     * [
     *  'start_time' => string, // Format YYYY-MM-DDTHH:MM:SS+-HH:MM or "now". Example: '2015-02-11T08:00:00-04:00'.
     *  'open_tracking' => bool,
     *  'click_tracking' => bool,
     *  'transactional' => bool,
     *  'sandbox' => bool,
     *  'skip_suppression' => bool,
     * ]
     *
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * @return string 64 bytes max
     */
    public function getCampaign()
    {
        return $this->_campaign;
    }

    /**
     * @param string $campaign
     * @return $this
     */
    public function setCampaign($campaign)
    {
        $this->_campaign = $campaign;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param string $description 1024 bytes max
     * @return $this
     */
    public function setDescription($description)
    {
        $this->_description = $description;

        return $this;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->_metadata;
    }

    /**
     * @param array $metadata
     * @return $this
     */
    public function setMetadata($metadata)
    {
        $this->_metadata = $metadata;

        return $this;
    }

    /**
     * @return array
     */
    public function getSubstitutionData()
    {
        return $this->_substitutionData;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setSubstitutionData($data)
    {
        $this->_substitutionData = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getReturnPath()
    {
        return $this->_returnPath;
    }

    /**
     * @param string $returnPath
     * @return $this
     */
    public function setReturnPath($returnPath)
    {
        $this->_returnPath = $returnPath;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUseDraftTemplate()
    {
        return $this->_useDraftTemplate;
    }

    /**
     * @param boolean $useDraftTemplate
     * @return $this
     */
    public function setUseDraftTemplate($useDraftTemplate)
    {
        $this->_useDraftTemplate = $useDraftTemplate;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->_attachments;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->_images;
    }

    /**
     * Prepares the message and gives it's array representation to send it through SparkSpot API
     * @see \SparkPost\Transmission::send()
     * @return array
     */
    public function toSparkPostArray()
    {

        $this->prepareCopyRecipients();

        $messageArray = [];

        foreach (self::$messageAttributesMap as $k => $v) {
            if (is_int($k)) {
                $attributeName = '_' . $v;
                $k = $v;
            } else {
                $attributeName = '_' . $v;
            }

            if (strpos($attributeName, '.') !== false) {
                list($attributeName, $key) = explode('.', $attributeName);
                $value = ArrayHelper::getValue($this->{$attributeName}, $key);
            } else {
                $value = $this->$attributeName;
            }

            if ($value || is_bool($value)) {
                $messageArray[$k] = $value;
            }
        }

        return $messageArray;
    }

    /**
     * Processes given emails and fill recipients field
     * @param array|string $emails
     * @param bool $copy adds header_to field with a placeholder to make the recipient(s) a CC/BCC copy
     */
    protected function addRecipient($emails, $copy = false)
    {
        // unset possible used list id, if user is going to set recipients
        unset($this->_to['list_id']);

        if (is_string($emails)) {
            $emails = [$emails];
        }

        foreach ($emails as $email => $name) {
            if (is_int($email)) {
                $address = [
                    'email' => trim($name),
                ];
            } else {
                $address = [
                    'name' => trim($name),
                    'email' => trim($email),
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
            $name = trim($name);

            if (is_int($email)) {
                $addresses[] = $name;
            } else {
                $email = trim($email);
                $addresses[] = "\"{$name}\" <{$email}>";
            }
        }

        return implode(',', $addresses);
    }

    /**
     * Goes through all recipients to find the main recipient
     * and replaces placeholder in 'header_to' field in copy recipients
     */
    private function prepareCopyRecipients()
    {
        $main = '';
        // find the main recipient
        foreach ($this->_to as $recipient) {
            if (!$main && !isset($recipient['address']['header_to'])) {
                $main = $recipient['address']['email'];
                break;
            }
        }

        if ($main) {
            foreach ($this->_to as &$recipient) {
                if (isset($recipient['address']['header_to'])) {
                    $recipient['address']['header_to'] = str_replace('%mainRecipient', $main,
                        $recipient['address']['header_to']);
                }
            }
        }
    }

    /**
     * Returns the MIME type of the given binary data
     * @param $content
     * @return string the binary MIME type
     */
    private function getBinaryMimeType($content)
    {
        $finfo = new \finfo(FILEINFO_MIME);

        return $finfo->buffer($content);
    }

    private function isListUsed()
    {
        return isset($this->_to['list_id']);
    }

    /**
     * Checks whether the $recipient is a copy (CC) recipient or BCC recipient, if $checkBcc is true.
     * CC differs from BCC: CC has email presented in 'Cc' header
     *
     * @param array $recipient ['email' => $email]
     * @param bool $checkBcc do we check whether the recipient is a CC recipient or BCC
     * @return bool
     */
    private function isCopyRecipient($recipient, $checkBcc = false)
    {
        if (!isset($recipient['header_to'])) {
            return false;
        }

        $result = $checkBcc ? true : false;
        $ccRecipients = explode(',', ArrayHelper::getValue($this->_headers, 'Cc', []));
        foreach ($ccRecipients as $ccRecipient) {
            if (preg_match("/^{$recipient['email']}$|<{$recipient['email']}>$/", $ccRecipient)) {
                // email is presented in Cc header

                // if we search Bcc, then if email is presented in Cc header - it's not a Bcc email
                $result = $checkBcc ? false : true;
                break;
            }
        }

        return $result;
    }
}
