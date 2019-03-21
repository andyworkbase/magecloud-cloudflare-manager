<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Block\Adminhtml\Manager;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

/**
 * Class Modal
 * @package MageCloud\CloudflareManager\Block\Adminhtml\Manager
 */
class Modal extends Template
{
    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var array|mixed
     */
    protected $types;

    /**
     * @var SerializerInterface
     */
    private $serializerInterface;

    /**
     * @var Json
     */
    private $serializerJson;

    /**
     * Modal constructor.
     * @param Template\Context $context
     * @param FormKey $formKey
     * @param array $data
     * @param SerializerInterface|null $serializerInterface
     * @param Json|null $serializerJson
     */
    public function __construct(
        Template\Context $context,
        FormKey $formKey,
        array $data = [],
        SerializerInterface $serializerInterface = null,
        Json $serializerJson = null
    ) {
        parent::__construct($context, $data);
        $this->formKey = $formKey;
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->types = isset($data['types']) ? $data['types'] : [];
        $this->serializerInterface = $serializerInterface ?: ObjectManager::getInstance()
            ->get(SerializerInterface::class);
        $this->serializerJson = $serializerJson ?: ObjectManager::getInstance()
            ->get(Json::class);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'formKey' => $this->getFormKey(),
            'url' => [
                'state' => $this->getStateActionUrl(),
                'purgeByUrl' => $this->getPurgeByUrlActionUrl(),
                'purgeAll' => $this->getPurgeAllActionUrl(),
                'configuration' => $this->getConfigurationUrl(),
            ]
        ];
    }

    /**
     * @return bool|false|string
     */
    public function getJsLayout()
    {
        return $this->serializerJson->serialize($this->jsLayout);
    }

    /**
     * @return bool|false|string
     */
    public function getSerializedConfig()
    {
        return $this->serializerJson->serialize($this->getConfig());
    }

    /**
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Create action url by path
     *
     * @param string $path
     * @return string
     */
    private function getActionUrl($path = '')
    {
        return $this->getUrl($path,
            [
                '_secure' => $this->getRequest()->isSecure()
            ]
        );
    }

    /**
     * Get url for check state Cloudflare service on site
     *
     * @return string
     */
    private function getStateActionUrl()
    {
        return $this->getActionUrl('cloudflare_manager/actions/state');
    }

    /**
     * Get url for purge Cloudflare service cache by separate URL(s)
     *
     * @return string
     */
    private function getPurgeByUrlActionUrl()
    {
        return $this->getActionUrl('cloudflare_manager/actions/purgeByUrl');
    }

    /**
     * Get url for purge Cloudflare service all cache on site
     *
     * @return string
     */
    private function getPurgeAllActionUrl()
    {
        return $this->getActionUrl('cloudflare_manager/actions/purgeAll');
    }

    /**
     * Get url for configuration page
     *
     * @return string
     */
    private function getConfigurationUrl()
    {
        return $this->getActionUrl('adminhtml/system_config/edit/section/cloudflare');
    }
}
