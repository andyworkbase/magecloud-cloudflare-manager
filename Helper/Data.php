<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package MageCloud\CloudflareManager\Helper
 */
class Data extends AbstractHelper
{
    /**
     * Cloudflare API endpoint
     */
    const CLOUDFLARE_DEFAULT_API_ENDPOINT = 'https://api.cloudflare.com/client/v4/zones/';

    /**
     * Use to check if site work through CloudFlare service
     */
    const CLOUDFLARE_KEY = 'cloudflare';

    /**
     * XML paths
     */
    const XML_PATH_ENABLED = 'cloudflare/general/enabled';
    const XML_PATH_EMAIL_ADDRESS = 'cloudflare/general/email_address';
    const XML_PATH_API_KEY = 'cloudflare/general/api_key';
    const XML_PATH_ZONE_ID = 'cloudflare/general/zone_id';
    const XML_PATH_API_ENDPOINT = 'cloudflare/general/api_endpoint';
    const XML_PATH_PURGE_AUTO = 'cloudflare/general/purge_auto';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $storeManager;
    }

    /**
     * @param null $store
     * @return bool|mixed
     */
    public function isEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool|mixed
     */
    public function getApiEndpoint($store = null)
    {
        if ($this->isEnabled($store)) {
            $value = $this->scopeConfig->getValue(
                self::XML_PATH_API_ENDPOINT,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
            return $value ?: self::CLOUDFLARE_DEFAULT_API_ENDPOINT;
        }

        return null;
    }

    /**
     * @param null $store
     * @return bool|mixed
     */
    public function getEmailAddress($store = null)
    {
        if ($this->isEnabled($store)) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_EMAIL_ADDRESS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * @param null $store
     * @return bool|mixed
     */
    public function getApiKey($store = null)
    {
        if ($this->isEnabled($store)) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_API_KEY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * @param null $store
     * @return bool|mixed
     */
    public function getZoneId($store = null)
    {
        if ($this->isEnabled($store)) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_ZONE_ID,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * @param null $store
     * @return bool|mixed
     */
    public function isPurgeAuto($store = null)
    {
        if ($this->isEnabled($store)) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_PURGE_AUTO,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isSiteWorkThroughCloudflare()
    {
        $headers = get_headers($this->_urlBuilder->getBaseUrl(), true);
        return isset($headers['Server']) && (strpos($headers['Server'], self::CLOUDFLARE_KEY) !== false);
    }
}