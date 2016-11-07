<?php

namespace AppBundle\Import\Segment\Merrick\Identity\Model\Transformer;

use AppBundle\Import\Segment\Merrick\Identity\Model\Transformer;

class IdentityAccountEmail extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->define('legacy.id', 'id', 'strval');
        $this->defineStatic('legacy.source', 'customer');
        $this->define('value', 'value', 'strtolower');
        $this->define('account', 'account', function($value) {
            return ['id' => $value, 'type' => 'identity-account'];
        });
        $this->defineStatic('isPrimary', true);
        $this->defineStatic('verification.verified', true);
    }
}