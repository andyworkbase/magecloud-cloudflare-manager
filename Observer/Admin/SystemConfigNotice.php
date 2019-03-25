<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Observer\Admin;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MageCloud\CloudflareManager\Helper\Data as HelperData;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class SystemConfigNotice
 * @package MageCloud\CloudflareManager\Observer\Admin
 */
class SystemConfigNotice implements ObserverInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * SystemConfig constructor.
     * @param HelperData $helperData
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        HelperData $helperData,
        ManagerInterface $messageManager
    ) {
        $this->helperData = $helperData;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $section = $observer->getRequest()->getParam('section');
        if (
            ($section == HelperData::CLOUDFLARE_KEY)
            && !$this->helperData->isSiteWorkThroughCloudflare()
        ) {
            $message = __('Site doesn\'t work through Cloudflare. Please check your Cloudflare service state 
                or connect your site to this service before using API.');
            $this->messageManager->addWarningMessage($message);
        }
    }
}
