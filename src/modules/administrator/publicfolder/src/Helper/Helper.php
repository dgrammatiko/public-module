<?php

/**
 * (C) 2023 Dimitrios Grammatikogiannis
 * GNU General Public License version 3 or later
 */

namespace Dgrammatiko\Module\Publicfolder\Administrator\Helper;

\defined('_JEXEC') || die();

use Joomla\CMS\Factory;
use stdClass;
use Symfony\Component\Console\Exception\LogicException;

class PublicfolderHelper
{
  public static function createAjax()
  {
    $app = Factory::getApplication();
    if (!$app->getSession()->checkToken()) {
      throw new \Exception('Not Allowed');
    }

    if ($app->getIdentity()->authorise('core.admin')) {
      throw new LogicException('Only administrators have permissions!', 403);
    }

    $dataRaw = file_get_contents(__DIR__ . '/data.json');

    try {
      $data = json_decode($dataRaw);
    } catch(\Exception $e) {
      throw new \Exception('Bad data');
    }

    if (!$data) {
      throw new \Exception('Bad data');
    }


  }

  private function createPublicFolder() {

  }

  private function getFolderContentAsObject($folder) {
    $obj = new stdClass;
    $arrFiles = [];
    $iterator = new FilesystemIterator(JPATH_ROOT . '/' . $folder);
    foreach ($iterator as $entry) {
      $arrFiles[] = $entry->getFilename();
    }

    foreach ($arrFiles as $file) {
      $name = str_replace(JPATH_ROOT, '', $file->name);
      $obj->{$name} = file_get_contents($file->name);
    }
  }
}
