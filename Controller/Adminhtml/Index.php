<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Controller\Adminhtml;

use Magento\Backend\App\Action;
use MageCloud\CloudflareManager\Helper\Data as HelperData;
use MageCloud\CloudflareManager\Model\ApiManagerFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Index
 * @package MageCloud\CloudflareManager\Controller\Adminhtml
 */
abstract class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MageCloud_CloudflareManager::cloudflare';

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ApiManagerFactory
     */
    protected $apiManagerFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Index constructor.
     * @param Action\Context $context
     * @param HelperData $helperData
     * @param ApiManagerFactory $apiManagerFactory
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        HelperData $helperData,
        ApiManagerFactory $apiManagerFactory,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->apiManagerFactory = $apiManagerFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
    }
}