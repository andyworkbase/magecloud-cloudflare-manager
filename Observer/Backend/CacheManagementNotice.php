<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Observer\Backend;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MageCloud\CloudflareManager\Helper\Data as HelperData;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class CacheManagementNotice
 * @package MageCloud\CloudflareManager\Observer\Backend
 */
class CacheManagementNotice implements ObserverInterface
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
        if (!$this->helperData->isSiteWorkThroughCloudflare()) {
            $message = __('Site doesn\'t work through Cloudflare. Please check your Cloudflare service state 
                or connect your site to this service before using manager.');
            $this->messageManager->addWarningMessage($message);
        }
    }
}
