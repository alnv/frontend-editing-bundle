<?php

namespace Alnv\FrontendEditingBundle\NotificationCenter;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\AnythingTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;

class FeChange implements NotificationTypeInterface
{

    public const NAME = 'feChange';

    public function __construct(private TokenDefinitionFactoryInterface $factory)
    {
        //
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [
            $this->factory->create(AnythingTokenDefinition::class, 'admin_email', 'mailer.admin_email'),
            $this->factory->create(AnythingTokenDefinition::class, 'form_*', 'mailer.form_*'),
            $this->factory->create(AnythingTokenDefinition::class, 'member_*', 'mailer.member_*')
        ];
    }
}