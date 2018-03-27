<?php

namespace AppBundle\Factory\Identity;

use AppBundle\Factory\AbstractEmbedFactory;
use AppBundle\Factory\Error;
use As3\Modlr\Models\AbstractModel;
use As3\Modlr\Models\Embed;
use As3\Modlr\Models\Model;
use As3\Modlr\Store\Store;

/**
 * Factory for identity account credentials
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class AccountCredentialsFactory extends AbstractEmbedFactory
{
    /**
     * @var AccountCredentialPasswordFactory
     */
    private $password;

    /**
     * @var AccountCredentialSocialFactory
     */
    private $social;

    /**
     * @param   Store                               $store
     * @param   AccountCredentialPasswordFactory    $password
     * @param   AccountCredentialSocialFactory      $social
     */
    public function __construct(Store $store, AccountCredentialPasswordFactory $password, AccountCredentialSocialFactory $social)
    {
//var_dump(__method__);
        parent::__construct($store);
        $this->password = $password;
        $this->social   = $social;
    }

    /**
     * Applies credential details to the password credentials models.
     *
     * @param   Embed       $credentials
     * @param   string      $clearPassword  The cleartext (unencoded) password.
     * @param   string      $mechanism
     * @param   string|null $username
     * @return  Embed
     */
    public function applyPasswordCredential(Embed $credentials, $clearPassword, $mechanism = 'platform', $username = null)
    {
var_dump(__method__);
        if (false === $this->supportsEmbed($credentials)) {
            $this->getUnsupportedError()->throwException();
        }

        $properties = [
            'value'     => $clearPassword,
            'mechanism' => $mechanism,
            'username'  => $username,
        ];
        $embedMeta = $credentials->getMetadata()->getEmbed('password')->embedMeta;
        $password  = $this->password->create($embedMeta, $properties);

        $credentials->set('password', $password);
        return $credentials;
    }

    /**
     * {@interitdoc}
     */
    public function canSave(AbstractModel $credentials)
    {
var_dump(__method__);
        if (false === $this->supportsEmbed($credentials)) {
            // Ensure this is the correct embed model.
            return $this->getUnsupportedError();
        }

        $this->preValidate($credentials);

        if (null !== $password = $credentials->get('password')) {
            // Ensure password credential can be saved.
            if (true !== $result = $this->getPasswordFactory()->canSave($password)) {
                return $result;
            }
        }
        return true;
    }

    /**
     * @return  AccountCredentialPasswordFactory
     */
    public function getPasswordFactory()
    {
var_dump(__method__);
        return $this->password;
    }

    /**
     * @return  AccountCredentialSocialFactory
     */
    public function getSocialFactory()
    {
var_dump(__method__);
        return $this->social;
    }

    /**
     * {@interitdoc}
     */
    public function postValidate(AbstractModel $credentials)
    {
var_dump(__method__);
        if (null !== $password = $credentials->get('password')) {
            $this->getPasswordFactory()->postValidate($password);
        }
    }

    /**
     * {@interitdoc}
     */
    public function preValidate(AbstractModel $credentials)
    {
var_dump(__method__);
        if (null !== $password = $credentials->get('password')) {
            $this->getPasswordFactory()->preValidate($password);
        }
    }

    /**
     * {@inheritodc}
     */
    protected function getSupportsType()
    {
var_dump(__method__);
        return 'identity-account-credentials';
    }
}
