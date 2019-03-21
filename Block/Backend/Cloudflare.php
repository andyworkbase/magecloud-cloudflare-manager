<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Block\Backend;

/**
 * Class Cloudflare
 * @package MageCloud\CloudflareManager\Block\Backend
 */
class Cloudflare extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Backend\Block\Widget\Container
     */
    protected function _prepareLayout()
    {
        $this->addCloudflareManagerButton();
        return parent::_prepareLayout();
    }

    /**
     * Add button to manage Cloudflare service
     *
     * @param $label
     */
    private function addCloudflareManagerButton()
    {
        if ($this->_authorization->isAllowed('MageCloud_CloudflareManager::cloudflare')) {
            $this->buttonList->add(
                'cloudflare_manager',
                [
                    'label' => __('Cloudflare Manager'),
                    'class' => 'cloudflare-manager primary'
                ]
            );
        }
    }
}