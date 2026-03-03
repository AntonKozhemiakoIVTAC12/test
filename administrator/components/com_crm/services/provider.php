<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\ServiceProvider;

use Crm\Component\Crm\Administrator\Service\StageMachine;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class CrmProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(StageMachine::class, function (Container $container) {
            return new StageMachine();
        });
    }
}
