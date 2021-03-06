<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Adapter;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\MessageInterface;
use Zend\Stdlib\ParametersInterface;
use ZendQueue\Exception;
use ZendQueue\Queue;

/**
 * Abstract class for performing common operations.
 *
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * User-provided options
     *
     * @var array
     */
    private $_options = array();

    /**
     * Default options
     *
     * @var array
     */
    protected $defaultOptions = array(
        'driverOptions' => array()
    );

    /**
     * Constructor.
     *
     * $options is an array of key/value pairs or an instance of Traversable
     * containing configuration options.
     *
     * @param  array|Traversable $options An array having configuration data
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Set options
     *
     * @param array|Traversable $options
     * @return AdapterInterface Fluent interface
     */
    public function setOptions($options)
    {

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        /*
         * Verify that adapter parameters are in an array.
        */
        if (!is_array($options)) {
            throw new Exception\InvalidArgumentException('Adapter options must be an array or Traversable object');
        }


        $adapterOptions = array();
        $driverOptions  = isset($this->_options['driverOptions']) ? $this->_options['driverOptions'] : array();

        if (array_key_exists('driverOptions', $options)) {
            // can't use array_merge() because keys might be integers
            foreach ((array)$options['driverOptions'] as $key => $value) {
                $driverOptions[$key] = $value;
            }
        }

        $this->_options = array_merge($this->defaultOptions, $options);
        $this->_options['driverOptions'] = $driverOptions;

        return $this;
    }

    /**
     * Returns the configuration options in this adapter.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * List avaliable params for sendMessage()
     *
     * @return array
     */
    public function getAvailableSendParams()
    {
        return array();
    }

    /**
     * List avaliable params for receiveMessages()
     *
     * @return array
     */
    public function getAvailableReceiveParams()
    {
        return array();
    }

    /**
     * Build info for received message
     *
     * @param mixed $handle
     * @param mixed $id
     * @param Queue|string $queue
     * @param ParametersInterface|array $options
     * @return array
     */
    protected function _buildMessageInfo($handle, $id, $queue, $options = null)
    {
        $name = $queue instanceof Queue ? $queue->getName() : (string) $queue;
        return array(
            'handle'    => $handle,
            'messageId' => $id,
            'queueId'   => $this->getQueueId($name),
            'queueName' => $name,
            'adapter'   => get_called_class(),
            'options'   => $options instanceof ParametersInterface ? $options->toArray() : (array) $options,
        );
    }

    /**
     * Embed info into a sended message
     *
     * @param Queue $queue
     * @param MessageInterface $message
     * @param mixed $id
     * @param ParametersInterface|array $options
     * @return void
     */
    protected function _embedMessageInfo(Queue $queue, MessageInterface $message, $id, $options = null)
    {
        $message->setMetadata($queue->getOptions()->getMessageMetadatumKey(), $this->_buildMessageInfo(false, $id, $queue, $options));
    }

    /**
     * Get message info
     *
     * Only received messages have embedded infos.
     *
     * @param Queue $queue
     * @param MessageInterface $message
     * @return array
     */
    public function getMessageInfo(Queue $queue, MessageInterface $message)
    {
       return $message->getMetadata($queue->getOptions()->getMessageMetadatumKey());
    }

    /**
     * @param Queue $queue
     * @param MessageInterface $message
     * @return void
     */
    protected function _cleanMessageInfo(Queue $queue, MessageInterface $message)
    {
        $metadatumKey = $queue->getOptions()->getMessageMetadatumKey();
        if ($message->getMetadata($metadatumKey, null)) {
            $message->setMetadata($metadatumKey, null);
        }
    }

}