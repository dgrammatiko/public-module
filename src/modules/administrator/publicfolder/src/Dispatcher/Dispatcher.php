<?php

/**
 * @copyright  (C) 2023 Dimitrios Grammatikogiannis
 * @license    GNU General Public License version 3 or later
 */

namespace Dgrammatiko\Module\Publicfolder\Administrator\Dispatcher;

\defined('_JEXEC') || die();

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
  use HelperFactoryAwareTrait;

  protected function getLayoutData()
  {
    //$this->getHelperFactory()->getHelper('PublicfolderHelper')->getXxxx($data['params'], $this->getApplication());
    return parent::getLayoutData();
  }
}
