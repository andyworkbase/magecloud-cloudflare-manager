<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace MageCloud\CloudflareManager\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;
use MageCloud\CloudflareManager\Helper\Data as HelperData;
use MageCloud\CloudflareManager\Model\ApiManagerFactory;

/**
 * Class PurgeByUrl
 *
 * Supported events:
 * - magecloud_cloudflare_purge_by_url_before
 * - magecloud_cloudflare_purge_by_url_after
 *
 * @package MageCloud\CloudflareManager\Console\Command
 */
class PurgeByUrl extends Command
{
    const URLS_ARGUMENT_KEY = 'urls';

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
     * PurgeByUrl constructor.
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
        $this->setName('magecloud:cloudflare-manager:purge-by-url')
            ->setDescription('Purge Cloudflare Cache For Specific URL(s)')
            ->addArgument(
                self::URLS_ARGUMENT_KEY,
                InputArgument::IS_ARRAY,
                'Specific URL(s)'
            );
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
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $urls = $input->getArgument(self::URLS_ARGUMENT_KEY);
        if (empty($urls)) {
            $output->writeln("<info>Please provide at least one URL to perform this operation.</info>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $validationResult = $this->validateUrls($urls);
        if (!empty($validationResult)) {
            $urls = implode("\n", $urls);
            $output->writeln("<error>Please provide a valid URL for records below. Protocol is required (http:// or https://):</error>");
            $output->writeln("<error>{$urls}</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $params = [
            'files' => $urls
        ];
        $this->eventManager->dispatch('magecloud_cloudflare_purge_by_url_before',
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
            $message = sprintf(
                'Successfully purged assets for URL(s) %s. Please allow up to 30 seconds for changes to take effect.',
                implode(', ', $urls)
            );
        } else {
            $message = 'Unexpected error. Please try again later.';
        }

        $this->eventManager->dispatch('magecloud_cloudflare_purge_by_url_after',
            ['params' => $params, 'response' => $response]
        );

        if ($isError) {
            $output->writeln("<error>{$message}</error>");
        } else {
            $output->writeln("<info>{$message}</info>");
        }

        return false;
    }

    /**
     * @param $urls
     * @return array
     */
    private function validateUrls($urls)
    {
        $errors = [];
        foreach ($urls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = $url;
            }
        }

        return $errors;
    }
}