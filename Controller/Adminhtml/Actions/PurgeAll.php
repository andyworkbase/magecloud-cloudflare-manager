<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Controller\Adminhtml\Actions;

use MageCloud\CloudflareManager\Controller\Adminhtml\Index;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class PurgeAll
 *
 * Supported events:
 * - magecloud_cloudflare_manager_purge_all_before
 * - magecloud_cloudflare_manager_purge_all_after
 *
 * @package MageCloud\CloudflareManager\Controller\Adminhtml\Actions
 */
class PurgeAll extends Index
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $responseContent = [];
        if (!$this->getRequest()->isAjax()) {
            $responseContent = [
                'errors' => true,
                'message' => __('Invalid request.')
            ];
            $resultJson->setData($responseContent);
            return $resultJson;
        }

        $params = [
            'purge_everything' => true
        ];

        $this->_eventManager->dispatch('magecloud_cloudflare_manager_purge_all_before',
            ['params' => $params, 'request' => $this->getRequest()]
        );

        /** @var \MageCloud\CloudflareManager\Model\ApiManager $manager */
        $manager = $this->apiManagerFactory->create();
        $response = $manager->buildRequest('purge_cache', 'POST', $params)
            ->sendRequest()
            ->getFormattedResponse();

        $isError = $response->getIsError() && !empty($response->getErrorMessage());
        if ($isError) {
            $responseContent['errors'] = true;
            $responseContent['message'] = $response->getErrorMessage();
        } else if ($successResult = $response->getSuccessResult()) {
            // ajax response content should only be shown in error case,
            // if result successful page will be refreshed with success message
            $successMessage = 'Successfully purged assets. Please allow up to 30 seconds for changes to take effect.';
            $this->messageManager->addSuccessMessage(__($successMessage));
        } else {
            $responseContent['errors'] = true;
            $responseContent['message'] = 'Unexpected error. Please try again later.';
        }

        $this->_eventManager->dispatch('magecloud_cloudflare_manager_purge_all_after',
            ['params' => $params, 'response' => $response]
        );

        $resultJson->setData($responseContent);
        return $resultJson;
    }
}