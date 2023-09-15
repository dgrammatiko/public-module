<?php

/**
 * @copyright  (C) 2023 Dimitrios Grammatikogiannis
 * @license    GNU General Public License version 3 or later
 */

namespace Dgrammatiko\Module\Publicfolder\Administrator\Helper;

\defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\PublicFolderGeneratorHelper;
use Joomla\CMS\Session\Session;


class PublicfolderHelper
{
  public static function createAjax()
  {
    if (Session::checkToken('post') && Factory::getUser()->authorise('core.admin')) {
      $helper = new PublicFolderGeneratorHelper();
      $folder = Factory::getApplication()->input->getString('folder', '');

      try {
        $helper->createPublicFolder($folder);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage(), 200);
      }
    }
  }
}
