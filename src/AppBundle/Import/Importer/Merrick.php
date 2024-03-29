<?php

namespace AppBundle\Import\Importer;

use AppBundle\Core\AccountManager;
use As3\SymfonyData\Import\Importer;
use As3\SymfonyData\Import\ImporterInterface;
use As3\SymfonyData\Import\PersisterInterface;
use As3\SymfonyData\Import\SourceInterface;

abstract class Merrick extends Importer implements ImporterInterface
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
        'cygnus:vspc'   => 'www.vehicleservicepros.com',
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
     * Returns the legacy domain for the current application context
     *
     * @return  string
     * @throws  InvalidArgumentException
     */
    public function getDomain()
    {
        $key = $this->accountManager->getCompositeKey();
        if (array_key_exists($key, $this->domains)) {
            return $this->domains[$key];
        }
        throw new \InvalidArgumentException(sprintf('Could not find legacy site value for account "%s!"', $key));
    }
}
