<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\RequestInterface;
use MageCloud\CloudflareManager\Helper\Data as HelperData;
use MageCloud\CloudflareManager\Model\ApiManagerFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class PurgeCache
 *
 * Supported events:
 * - magecloud_cloudflare_manager_purge_all_before
 * - magecloud_cloudflare_manager_purge_all_after
 *
 * @package MageCloud\CloudflareManager\Observer
 */
class PurgeCache implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ApiManagerFactory
     */
    protected $apiManagerFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * PurgeCloudflareCache constructor.
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param RequestInterface $request
     * @param HelperData $helperData
     * @param ApiManagerFactory $apiManagerFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        RequestInterface $request,
        HelperData $helperData,
        ApiManagerFactory $apiManagerFactory,
        ManagerInterface $messageManager
    ) {
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->apiManagerFactory = $apiManagerFactory;
        $this->helperData = $helperData;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $isEnabled = $this->helperData->isEnabled();
        $isPurgeAuto = $this->helperData->isPurgeAuto();
        if ($isEnabled && $isPurgeAuto) {
            $params = [
                'purge_everything' => true
            ];

            $this->eventManager->dispatch('magecloud_cloudflare_manager_purge_all_before',
                ['params' => $params, 'request' => $this->request]
            );

            /** @var \MageCloud\CloudflareManager\Model\ApiManager $manager */
            $manager = $this->apiManagerFactory->create();
            $response = $manager->buildRequest('purge_cache', 'POST', $params)
                ->sendRequest()
                ->getFormattedResponse();

            $isError = $response->getIsError() && !empty($response->getErrorMessage());
            if ($isError) {
                $message = $response->getErrorMessage();
            } else if ($successResult = $response->getSuccessResult()) {
                $message = 'Successfully purged assets. Please allow up to 30 seconds for changes to take effect.';
            } else {
                $message = 'Unexpected error. Please try again later.';
            }

            $this->eventManager->dispatch('magecloud_cloudflare_manager_purge_all_after',
                ['params' => $params, 'response' => $response]
            );

            if ($isError) {
                $this->messageManager->addErrorMessage(__($message));
            } else {
                $this->messageManager->addSuccessMessage(__($message));
            }
        }

        return $this;
    }
}