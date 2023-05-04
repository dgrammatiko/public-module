<?php

/**
 * (C) 2023 Dimitrios Grammatikogiannis
 * GNU General Public License version 3 or later
 */

namespace Dgrammatiko\Module\Publicfolder\Administrator\Helper;

\defined('_JEXEC') || die();

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;
use Symfony\Component\Console\Exception\LogicException;

class PublicfolderHelper
{
  public static function createAjax()
  {
    if (Session::checkToken('post') && Factory::getUser()->authorise('core.admin')) {
      self::createPublicFolder();
    }

    throw new LogicException('You shall not pass', 200);
  }

  private static function createPublicFolder() {
    $input = Factory::getApplication()->input;
    // Remove the last (Windows || NIX) slash
    $folder = rtrim($input->getString('folder', ''), '/');
    $folder = rtrim($folder, '\\');

    if (!is_dir($folder)) {
      if (!mkdir($folder, 0755, true)) {
        throw new LogicException('The given directory doesn\'t exist or not accessible due to wrong permissions', 200);
      }
    }

    // Create the required folders
    if (
      !mkdir($folder . '/administrator/components/com_joomlaupdate', 0755, true)
      || !mkdir($folder . '/administrator/includes', 0755, true)
      || !mkdir($folder . '/api/includes', 0755, true)
      || !mkdir($folder . '/includes', 0755)
    ) {
      throw new LogicException('Unable to write on the given directory, check the permissions', 200);
    }

    // if (is_dir($folder)) {
    //   throw new LogicException('The given directory doesn\'t exist or not accessible due to wrong permissions', 200);
    // }

    // Create symlink for the media folder
    if (!symlink(JPATH_ROOT . '/media', $folder . '/media')) {
      throw new LogicException('Unable to symlink the directory: "media"', 200);
    }

    // Create symlinks to all the local filesystem directories
    if (PluginHelper::isEnabled('filesystem', 'local')) {
      $local = PluginHelper::getPlugin('filesystem', 'local');
      $localDirectories = json_decode((new Registry($local->params))->get('directories', '[{"directory":"images"}]'));

      foreach($localDirectories as $localDirectory) {
        if (!symlink(JPATH_ROOT . '/' . $localDirectory->directory, $folder . '/' . $localDirectory->directory)) {
          throw new LogicException('Unable to symlink the directory: "' . $localDirectory->directory . '"', 200);
        }
      }
    }

    // Copy static files
    file_put_contents($folder . '/includes/incompatible.html', file_get_contents(JPATH_ROOT . '/templates/system/incompatible.html'));
    file_put_contents($folder . '/includes/build_incomplete.html', file_get_contents(JPATH_ROOT . '/templates/system/build_incomplete.html'));

    // Create the index.php both for root, api and administrator
    $indexRaw = file_get_contents(JPATH_ROOT . '/index.php');
    $indexRaw = str_replace('/../templates/system/', '/templates/system/', $indexRaw);
    $indexRaw = str_replace('/templates/system/','/includes/', $indexRaw);
    file_put_contents($folder . '/index.php', $indexRaw);
    file_put_contents($folder . '/administrator/index.php', $indexRaw);
    file_put_contents($folder . '/api/index.php', file_get_contents(JPATH_ROOT . '/api/index.php'));

    // Copy the robots
    if (is_file(JPATH_ROOT . '/robots.txt')) {
      file_put_contents($folder . '/robots.txt', file_get_contents(JPATH_ROOT . '/robots.txt'));
    } elseif (is_file(JPATH_ROOT . '/robots.txt.dist')) {
      file_put_contents($folder . '/robots.txt.dist', file_get_contents(JPATH_ROOT . '/robots.txt.dist'));
    }

    // Copy the apache config
    if (is_file(JPATH_ROOT . '/.htaccess')) {
      file_put_contents($folder . '/.htaccess', file_get_contents(JPATH_ROOT . '/.htaccess'));
    } elseif (is_file(JPATH_ROOT . '/htaccess.txt')) {
      file_put_contents($folder . '/htaccess.txt', file_get_contents(JPATH_ROOT . '/htaccess.txt'));
    }

    // Populate the includes
    $tmp = file_get_contents(JPATH_ROOT . '/includes/app.php');
    $tmp = str_replace("JPATH_ROOT . '/media/vendor'", "JPATH_PUBLIC . '/media/vendor'", $tmp);
    file_put_contents($folder . '/includes/app.php', $tmp);

    $tmp = file_get_contents(JPATH_ROOT . '/includes/defines.php');
    $tmp = str_replace("define('JPATH_SITE', JPATH_ROOT);", "define('JPATH_SITE', JPATH_ROOT);\ndefine('JPATH_PUBLIC', $folder);", $tmp);
    file_put_contents($folder . '/includes/defines.php', $tmp);

    $tmp = file_get_contents(JPATH_ROOT . '/includes/framework.php');
    $tmp = str_replace("header('Location: ' . substr(\$_SERVER['REQUEST_URI'], 0, strpos(\$_SERVER['REQUEST_URI'], 'index.php')) . 'installation/index.php');", "echo file_get_contents(\$folder . '/includes/build_incomplete.html');", $tmp);
    file_put_contents($folder . '/includes/framework.php', $tmp);

    // Populate the administrator/includes
    $tmp = file_get_contents(JPATH_ROOT . '/administrator/includes/app.php');
    $tmp = str_replace("JPATH_ROOT . '/media/vendor'", "JPATH_PUBLIC . '/media/vendor'", $tmp);
    file_put_contents($folder . '/administrator/includes/app.php', $tmp);

    $tmp = file_get_contents(JPATH_ROOT . '/administrator/includes/defines.php');
    $tmp = str_replace("define('JPATH_SITE', JPATH_ROOT);", "define('JPATH_SITE', JPATH_ROOT);\ndefine('JPATH_PUBLIC', $folder);", $tmp);
    file_put_contents($folder . '/administrator/includes/defines.php', $tmp);

    $tmp = file_get_contents(JPATH_ROOT . '/administrator/includes/framework.php');
    $tmp = str_replace("header('Location: ../installation/index.php');", "echo file_get_contents(\$folder . '/includes/build_incomplete.html');", $tmp);
    file_put_contents($folder . '/administrator/includes/framework.php', $tmp);

    // Populate the api/includes
    file_put_contents($folder . '/api/includes/app.php', file_get_contents(JPATH_ROOT . '/api/includes/app.php'));

    $tmp = file_get_contents(JPATH_ROOT . '/api/includes/defines.php');
    $tmp = str_replace("define('JPATH_SITE', JPATH_ROOT);", "define('JPATH_SITE', JPATH_ROOT);\ndefine('JPATH_PUBLIC', $folder);", $tmp);
    file_put_contents($folder . '/api/includes/defines.php', $tmp);

    file_put_contents($folder . '/api/includes/framework.php', file_get_contents(JPATH_ROOT . '/api/includes/framework.php'));
    file_put_contents(dirname(dirname(__DIR__)) . '/engaged.json', '{ "publicPath": "'. $folder . '" }');
  }
}
