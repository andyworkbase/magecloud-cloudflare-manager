<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use MageCloud\CloudflareManager\Helper\Data as HelperData;
use MageCloud\CloudflareManager\Model\ApiManagerFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class State
 * @package MageCloud\CloudflareManager\Console\Command
 */
class State extends Command
{
    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ApiManagerFactory
     */
    protected $apiManagerFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * State constructor.
     * @param AppState $state
     * @param HelperData $helperData
     * @param ApiManagerFactory $apiManagerFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AppState $state,
        HelperData $helperData,
        ApiManagerFactory $apiManagerFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct();
        $this->appState = $state;
        $this->apiManagerFactory = $apiManagerFactory;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magecloud:cloudflare-manager:state')
            ->setDescription('Cloudflare State');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $isEnabled = $this->helperData->isEnabled();
        if (!$isEnabled) {
            $output->writeln("<info>Enabled module to perform this operation.</info>");
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        /** @var \MageCloud\CloudflareManager\Model\ApiManager $manager */
        $manager = $this->apiManagerFactory->create();
        $response = $manager->buildRequest('', 'PATCH')
            ->sendRequest()
            ->getFormattedResponse();

        $isError = $response->getIsError() && !empty($response->getErrorMessage());
        if ($isError) {
            $resultMessage = $response->getErrorMessage();
        } else if ($successResult = $response->getSuccessResult()) {
            $storeName = $successResult->getName() ?: $this->getStoreFrontendName();
            $resultMessage = sprintf(
                'Cloudflare status for site %s - %s',
                $storeName,
                $successResult->getStatus()
            );
        } else {
            $resultMessage = 'Unexpected error. Please try again later.';
        }

        if ($isError) {
            $output->writeln("<error>{$resultMessage}</error>");
        } else {
            $output->writeln("<info>{$resultMessage}</info>");
        }

        return false;
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