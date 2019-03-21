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
 * @package MageCloud\CloudflareManager\Controller\Adminhtml\Actions
 */
class State extends Index
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        // put the mark that response content should be displayed in popup window
        $responseContent = [
            'needShowResponse' => true
        ];
        if (!$this->getRequest()->isAjax()) {
            $responseContent['errors'] = true;
            $responseContent['message'] = __('Invalid request.');
            $resultJson->setData($responseContent);
            return $resultJson;
        }

        /** @var \MageCloud\CloudflareManager\Model\ApiManager $manager */
        $manager = $this->apiManagerFactory->create();
        $response = $manager->buildRequest('', 'PATCH')
            ->sendRequest()
            ->getFormattedResponse();

        if ($response->getIsError() && !empty($response->getErrorMessage())) {
            $responseContent['errors'] = true;
            $responseContent['message'] = $response->getErrorMessage();
        } else if ($successResult = $response->getSuccessResult()) {
            $storeName = $successResult->getName() ?: $this->getStoreFrontendName();
            $message = sprintf(
                'Cloudflare status for site %s - <strong>%s</strong>',
                $storeName,
                $successResult->getStatus()
            );
            $responseContent['message'] = $message;
        } else {
            $responseContent['errors'] = true;
            $responseContent['message'] = 'Unexpected error. Please try again later.';
        }

        $resultJson->setData($responseContent);
        return $resultJson;
    }

    /**
     * @param string $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStoreFrontendName($storeId = '')
    {
        return $this->storeManager->getStore($storeId)->getFrontendName();
    }
}