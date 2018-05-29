<?php

namespace AppBundle\Import\Importer;

use AppBundle\Core\AccountManager;
use As3\SymfonyData\Import\Importer;
use As3\SymfonyData\Import\ImporterInterface;
use As3\SymfonyData\Import\PersisterInterface;
use As3\SymfonyData\Import\SourceInterface;

abstract class Platform extends Importer implements ImporterInterface
{
    /**
     * @var     AccountManager
     */
    protected $accountManager;

    /**
     * {@inheritdoc}
     */
    protected $supportedContexts = [
        'default'
    ];

    /**
     * @var     array
     * Hash containing legacy query criteria for applications
     */
    private $domains = [
        'acbm:ooh'      => 'www.oemoffhighway.com',
        'acbm:fcp'      => 'www.forconstructionpros.com',
        'cygnus:vspc'   => 'www.vehicleservicepros.com',
        'cygnus:ofcr'   => 'www.officer.com',
        'acbm:sdce'     => 'www.sdcexec.com',
        'acbm:fl'       => 'www.foodlogistics.com',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(AccountManager $accountManager, PersisterInterface $persister, SourceInterface $source)
    {
        $this->accountManager = $accountManager;
        return parent::__construct($persister, $source);
    }

    /**
     *  Set source for base collections
     */
    public function setSourceDatabase($collection = 'platform') {
        $dbName = sprintf('%s_%s_%s', $this->accountManager->getAccountKey(), $this->accountManager->getApplicationKey(), $collection);
        $this->source->setDatabase($dbName);
    }

    /**
     * Returns the legacy domain for the current application context
     *
     * @return  string
     * @throws  InvalidArgumentException
     */
    public function getDomain($key = null)
    {    
        if (null === $key) {
            $key = $this->accountManager->getCompositeKey();
        }
        if (array_key_exists($key, $this->domains)) {
            return $this->domains[$key];
        }
        throw new \InvalidArgumentException(sprintf('Could not find legacy site value for account "%s!"', $key));
    }

    /**
     * Returns the group key for the current context
     *
     * @return  string
     * @throws  InvalidArgumentException
     */
    public function getGroupKey()
    {
        return $this->accountManager->getApplication()->get('key');
    }
}
