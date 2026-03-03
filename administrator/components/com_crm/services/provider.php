<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_crm
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Crm\Component\Crm\Administrator\Extension\CrmComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->registerServiceProvider(new MVCFactory('\\Crm\\Component\\Crm'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Crm\\Component\\Crm'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new CrmComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );
    }
};
