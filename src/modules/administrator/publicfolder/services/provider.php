<?php

/**
 * (C) 2023 Dimitrios Grammatikogiannis
 * GNU General Public License version 3 or later
 */

\defined('_JEXEC') || die();

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The Publicfolder module service provider.
 *
 * @since  0.0.1
 */
return new class implements ServiceProviderInterface
{
  /**
   * Registers the service provider with a DI container.
   *
   * @param   Container  $container  The DI container.
   *
   * @return  void
   *
   * @since   0.0.1
   */
  public function register(Container $container)
  {
    $container->registerServiceProvider(new ModuleDispatcherFactory('\\Dgrammatiko\\Module\\Publicfolder'))
      ->registerServiceProvider(new HelperFactory('\\Dgrammatiko\\Module\\Publicfolder\\Administrator\\Helper'))
      ->registerServiceProvider(new Module());
  }
};
