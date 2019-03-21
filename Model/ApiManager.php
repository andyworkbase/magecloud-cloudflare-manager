<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Model;

use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\CacheInvalidate\Model\SocketFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use MageCloud\CloudflareManager\Helper\Data as HelperData;
use MageCloud\CloudflareManager\Model\Serializer;
use Magento\Framework\App\ObjectManager;

/**
 * API manager for Cloudflare service
 *
 * Class ApiManager
 * @package MageCloud\CloudflareManager\Model
 */
class ApiManager extends \Magento\Framework\DataObject
{
    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var SocketFactory
     */
    protected $socketAdapterFactory;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * API headers
     *
     * @var array
     */
    private $headers = [
        "Content-Type: application/json"
    ];

    /**
     * API params
     *
     * @var array
     */
    private $params = [];

    /**
     * Cloudflare API endpoint
     *
     * @var null
     */
    private $url = '';

    /**
     * Cloudflare API Key
     *
     * @var string
     */
    private $apiKey = '';

    /**
     * Eail address associated with Cloudflare account
     *
     * @var string
     */
    private $email = '';

    /**
     * Cloudflare Zone ID
     *
     * @var string
     */
    private $zoneId = '';

    /**
     * Response from API request
     *
     * @var null
     */
    private $response = null;

    /**
     * API request method
     *
     * @var null
     */
    private $requestMethod;

    /**
     * Errors recollected after each API call
     *
     * @var array
     */
    protected $callErrors = [];

    /**
     * ApiManager constructor.
     * @param CurlFactory $curlFactory
     * @param SocketFactory $socketAdapterFactory
     * @param EncryptorInterface $encryptor
     * @param HelperData $helperData
     * @param \MageCloud\CloudflareManager\Model\Serializer|null $serializer
     * @param array $data
     */
    public function __construct(
        CurlFactory $curlFactory,
        SocketFactory $socketAdapterFactory,
        EncryptorInterface $encryptor,
        HelperData $helperData,
        Serializer $serializer = null,
        array $data = []
    ) {
        parent::__construct($data);
        $this->curlFactory = $curlFactory;
        $this->socketAdapterFactory = $socketAdapterFactory;
        $this->encryptor = $encryptor;
        $this->helperData = $helperData;
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Serializer::class);
    }

    /**
     * As for API Key field in configuration we use type 'obscure' we need to decrypt value before use
     *
     * @return string
     */
    private function getDecryptedApiKey()
    {
        return $this->encryptor->decrypt(trim($this->helperData->getApiKey()));
    }

    /**
     * Prepare API authorization headers for request
     *
     * @return bool
     */
    private function prepareAuthorizationHeaders()
    {
        $apiKey = $this->getDecryptedApiKey();
        $email = trim($this->helperData->getEmailAddress());
        if (!$apiKey || !$email) {
            return false;
        }

        if (!$this->apiKey) {
            $this->apiKey = $apiKey;
        }
        if (!$this->email) {
            $this->email = $email;
        }

        $this->headers = array_merge($this->headers, [
            "X-Auth-Email: {$this->email}",
            "X-Auth-Key: {$this->apiKey}"
        ]);

        return true;
    }

    /**
     * Prepare API url for request
     *
     * @param $action
     * @return bool|string|null
     */
    private function prepareApiUrl($action)
    {
        $zoneId = trim($this->helperData->getZoneId());
        // omit 'action' argument check, as he can be empty (for example for check 'status' information)
        if (!$zoneId) {
            return false;
        }

        if (!$this->zoneId) {
            $this->zoneId = $zoneId;
        }

        $apiEndpoint = $this->helperData->getApiEndpoint();
        if (!$this->url) {
            $this->url = sprintf($apiEndpoint . '%s/%s/', $this->zoneId, $action);
        }

        return $this->url;
    }

    /**
     * @param $response
     */
    private function setResponse($response)
    {
        if (!$this->response || (!$this->response instanceof \Magento\Framework\DataObject)) {
            $this->response = $response;
        } else {
            $this->response->setData($response);
        }
    }

    /**
     * @return null
     */
    private function getResponse()
    {
        return $this->response;
    }

    /**
     * Prepare action(s) before request
     *
     * @param string $action
     * @param string $method
     * @param array $params
     * @param array $headers
     * @return $this
     */
    public function buildRequest(
        $action = '',
        $method = \Zend_Http_Client::POST,
        $params = [],
        $headers = []
    ) {
        $this->setResponse(new \Magento\Framework\DataObject([]));

        if (!$this->prepareAuthorizationHeaders()) {
            $this->setResponse([
                'error_message' => __('API Key, Email Address related to Cloudflare account must be set up before using API calls.')
            ]);
            return $this;
        }
        if (!$this->prepareApiUrl($action)) {
            $this->setResponse([
                'error_message' => __('Zone ID related to Cloudflare account must be set up before using API calls.')
            ]);
            return $this;
        }

        $this->headers = array_merge($this->headers, $headers);
        $this->params = array_merge($this->params, $params);

        if (!$this->requestMethod) {
            $this->requestMethod = $method;
        }

        return $this;
    }

    /**
     * Send request to Cloudflare service by API
     *
     * @return $this
     */
    public function sendRequest()
    {
        $response = $this->getResponse();
        if ($response->getErrorMessage()) {
            // return if internal error
            return $this;
        }

        try {
            /** @var \Magento\Framework\HTTP\Adapter\Curl $curl */
            $curl = $this->curlFactory->create();
            $curl->write(
                $this->requestMethod,
                $this->url,
                '1.1',
                $this->headers,
                $this->serializer->serialize($this->params)
            );
            $result = $curl->read();
            if ($curl->getErrno()) {
                $this->setResponse([
                    'error_message' => sprintf(
                        'Cloudflare API service connection error #%s: %s',
                        $curl->getErrno(),
                        $curl->getError()
                    )
                ]);
                return $this;
            }
            $result = \Zend_Http_Response::fromString($result);
            $responseBody = $result->getBody();
            if (!is_string($responseBody)) {
                $this->setResponse([
                    'error_message' => 'Invalid or empty response.'
                ]);
                return $this;
            }
            $result = $this->serializer->unserialize($responseBody);
            $this->setResponse($result);
            $curl->close();
        } catch (\Exception $e) {
            $this->setResponse([
                'error_message' => $e->getMessage()
            ]);
        }

        return $this;
    }

    /**
     * @param $errors
     */
    private function extractErrors($errors)
    {
        foreach ($errors as $key => $error) {
            $code = isset($error['code']) ? $error['code'] : '';
            $message = isset($error['message']) ? $error['message'] : '';
            $fullMessage = sprintf('#%s: %s', $code, $message);
            $this->callErrors[$key][] = $fullMessage;
            $errorChain = isset($error['error_chain']) ? $error['error_chain'] : [];
            if (!empty($errorChain)) {
                $this->extractErrors($errorChain);
            }
        }
        // format errors
        if (!empty($this->callErrors)) {
            array_walk(
                $this->callErrors,
                function (&$value) {
                    if (is_array($value)) {
                        $value = implode("\n", $value);
                    }
                }
            );
        }
    }

    /**
     * @return null
     */
    public function getFormattedResponse()
    {
        if ($response = $this->getResponse()) {
            $response->setIsError(true);
            $errors = $response->getErrors();
            if ($internalError = $response->getErrorMessage()) {
                // authorization, connection, internal, etc... error(s)
                return $response;
            } else if (!empty($errors)) {
                // cloudflare response error(s)
                $this->extractErrors($errors);
                if (!empty($this->callErrors)) {
                    $errorMessage = implode("\n", $this->callErrors);
                    $response->setErrorMessage($errorMessage);
                }
            } else if ($response->getSuccess() && ($data = $response->getResult())) {
                $response->setIsError(false);
                // result content will be retrieved in related context (controller, console, etc...)
                $result = new \Magento\Framework\DataObject();
                $result->setData($data);
                $response->setSuccessResult($result);
            } else {
                $response->setErrorMessage(__('Unexpected error. Please try again later.'));
            }
        }

        return $response;
    }
}