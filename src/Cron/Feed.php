<?php

declare(strict_types=1);

namespace Omikron\Factfinder\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Omikron\FactFinder\Communication\Resource\Builder;
use Omikron\Factfinder\Model\Api\PushImport;
use Omikron\Factfinder\Model\Config\CommunicationConfig;
use Omikron\Factfinder\Model\Export\FeedFactory as FeedGeneratorFactory;
use Omikron\Factfinder\Model\FtpUploader;
use Omikron\Factfinder\Model\StoreEmulation;
use Omikron\Factfinder\Model\Stream\CsvFactory;
use Omikron\Factfinder\Service\FeedFileService;

class Feed
{
    private const PATH_CONFIGURABLE_CRON_IS_ENABLED = 'factfinder/configurable_cron/ff_cron_enabled';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var StoreEmulation */
    private $storeEmulation;

    /** @var FeedGeneratorFactory */
    private $feedGeneratorFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CommunicationConfig */
    private $communicationConfig;

    /** @var CsvFactory */
    private $csvFactory;

    /** @var FtpUploader */
    private $ftpUploader;

    /** @var PushImport */
    private $pushImport;

    /** @var string */
    private $feedType;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        FeedGeneratorFactory $feedFactory,
        StoreEmulation $emulation,
        CsvFactory $csvFactory,
        FtpUploader $ftpUploader,
        CommunicationConfig $communicationConfig,
        PushImport $pushImport,
        string $type
    ) {
        $this->scopeConfig          = $scopeConfig;
        $this->storeManager         = $storeManager;
        $this->feedGeneratorFactory = $feedFactory;
        $this->storeEmulation       = $emulation;
        $this->csvFactory           = $csvFactory;
        $this->ftpUploader          = $ftpUploader;
        $this->communicationConfig  = $communicationConfig;
        $this->pushImport           = $pushImport;
        $this->feedType             = $type;
    }

    public function execute(): void
    {
        if (!$this->scopeConfig->isSetFlag(self::PATH_CONFIGURABLE_CRON_IS_ENABLED)) {
            return;
        }

        foreach ($this->storeManager->getStores() as $store) {
            $this->storeEmulation->runInStore((int) $store->getId(), function () use ($store) {
                $storeId = (int) $store->getId();
                if ($this->communicationConfig->isChannelEnabled($storeId)) {
                    $filename = (new FeedFileService())->getFeedExportFilename($this->feedType, $this->communicationConfig->getChannel());
                    $stream   = $this->csvFactory->create(['filename' => "factfinder/{$filename}"]);
                    $this->feedGeneratorFactory->create($this->feedType)->generate($stream);
                    $this->ftpUploader->upload($filename, $stream);
                    if ($this->communicationConfig->isPushImportEnabled($storeId)) {
                        $this->pushImport->execute($storeId);
                    }
                }
            });
        }
    }
}
