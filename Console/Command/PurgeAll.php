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

/**
 * Class PurgeAll
 *
 * Supported events:
 * - magecloud_cloudflare_manager_purge_all_before
 * - magecloud_cloudflare_manager_purge_all_after
 *
 * @package MageCloud\CloudflareManager\Console\Command
 */
class PurgeAll extends Command
{
    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ApiManagerFactory
     */
    protected $apiManagerFactory;

    /**
     * PurgeAll constructor.
     * @param AppState $state
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param HelperData $helperData
     * @param ApiManagerFactory $apiManagerFactory
     */
    public function __construct(
        AppState $state,
        \Magento\Framework\Event\Manager $eventManager,
        HelperData $helperData,
        ApiManagerFactory $apiManagerFactory
    ) {
        parent::__construct();
        $this->appState = $state;
        $this->eventManager = $eventManager;
        $this->apiManagerFactory = $apiManagerFactory;
        $this->helperData = $helperData;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magecloud:cloudflare-manager:purge-all')
            ->setDescription('Purge Cloudflare Cache');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $isEnabled = $this->helperData->isEnabled();
        if (!$isEnabled) {
            $output->writeln("<info>Enabled module to perform this operation.</info>");
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        $params = [
            'purge_everything' => true
        ];

        $this->eventManager->dispatch('magecloud_cloudflare_manager_purge_all_before',
            ['params' => $params, 'request' => null]
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
            $output->writeln("<error>{$message}</error>");
        } else {
            $output->writeln("<info>{$message}</info>");
        }

        return false;
    }
}