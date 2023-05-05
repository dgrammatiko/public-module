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
      $input = Factory::getApplication()->input;
      // Remove the last (Windows || NIX) slash
      $folder = rtrim($input->getString('folder', ''), '/');
      $folder = rtrim($folder, '\\');

      self::createPublicFolder($folder);
    }

    throw new LogicException('You shall not pass', 200);
  }

  private static function createSymlink($source, $dest) {
    if (!symlink($source, $dest)) {
      throw new LogicException('Unable to symlink the file: ' . str_replace(JPATH_ROOT, '', $source), 200);
    }
  }

  private static function createFile($path, $content){
    if (!file_put_contents($path, $content)) {
      throw new LogicException('Unable to create the file: ' . str_replace(JPATH_ROOT, '', $path), 200);
    }
  }

  private static function createPublicFolder($folder) {
    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
      throw new LogicException('The given directory doesn\'t exist or not accessible due to wrong permissions', 200);
    }

    // Create the required folders
    if (!mkdir($folder . '/administrator/components/com_joomlaupdate', 0755, true)
      || !mkdir($folder . '/administrator/includes', 0755, true)
      || !mkdir($folder . '/api/includes', 0755, true)
      || !mkdir($folder . '/includes', 0755)) {
      throw new LogicException('Unable to write on the given directory, check the permissions', 200);
    }

    // Create symlink for the joomla update entry point
    self::createSymlink(JPATH_ROOT . '/administrator/components/com_joomlaupdate/extract.php', $folder . '/administrator/components/com_joomlaupdate/extract.php');

    // Create symlink for the media folder
    self::createSymlink(JPATH_ROOT . '/media', $folder . '/media');

    // Create symlinks to all the local filesystem directories
    if (PluginHelper::isEnabled('filesystem', 'local')) {
      $local = PluginHelper::getPlugin('filesystem', 'local');
      $localDirectories = json_decode((new Registry($local->params))->get('directories', '[{"directory":"images"}]'));

      foreach($localDirectories as $localDirectory) {
        self::createSymlink(JPATH_ROOT . '/' . $localDirectory->directory, $folder . '/' . $localDirectory->directory);
      }
    }

    // Create the index.php both for root, api and administrator
    self::createFile($folder . '/index.php', file_get_contents(JPATH_ROOT . '/index.php'));
    self::createFile($folder . '/administrator/index.php', file_get_contents(JPATH_ROOT . '/index.php'));
    self::createFile($folder . '/api/index.php', file_get_contents(JPATH_ROOT . '/api/index.php'));

    // Copy the robots
    if (is_file(JPATH_ROOT . '/robots.txt')) {
      self::createFile($folder . '/robots.txt', file_get_contents(JPATH_ROOT . '/robots.txt'));
    } elseif (is_file(JPATH_ROOT . '/robots.txt.dist')) {
      self::createFile($folder . '/robots.txt.dist', file_get_contents(JPATH_ROOT . '/robots.txt.dist'));
    }

    // Copy the apache config
    if (is_file(JPATH_ROOT . '/.htaccess')) {
      self::createFile($folder . '/.htaccess', file_get_contents(JPATH_ROOT . '/.htaccess'));
    } elseif (is_file(JPATH_ROOT . '/htaccess.txt')) {
      self::createFile($folder . '/htaccess.txt', file_get_contents(JPATH_ROOT . '/htaccess.txt'));
    }

    // Populate the includes
    self::createFile($folder . '/includes/app.php', file_get_contents(JPATH_ROOT . '/includes/app.php'));
    self::createFile($folder . '/includes/defines.php', file_get_contents(JPATH_ROOT . '/includes/defines.php'));
    self::createFile($folder . '/includes/framework.php', file_get_contents(JPATH_ROOT . '/includes/framework.php'));

    // Populate the administrator/includes
    self::createFile($folder . '/administrator/includes/app.php', file_get_contents(JPATH_ROOT . '/administrator/includes/app.php'));
    self::createFile($folder . '/administrator/includes/defines.php', str_replace("'JPATH_PUBLIC', JPATH_ROOT", "'JPATH_PUBLIC', $folder", file_get_contents(JPATH_ROOT . '/administrator/includes/defines.php')));
    self::createFile($folder . '/administrator/includes/framework.php', file_get_contents(JPATH_ROOT . '/administrator/includes/framework.php'));

    // Populate the api/includes
    self::createFile($folder . '/api/includes/app.php', file_get_contents(JPATH_ROOT . '/api/includes/app.php'));
    self::createFile($folder . '/api/includes/defines.php', str_replace("'JPATH_PUBLIC', JPATH_ROOT", "'JPATH_PUBLIC', $folder", file_get_contents(JPATH_ROOT . '/api/includes/defines.php')));
    self::createFile($folder . '/api/includes/framework.php', file_get_contents(JPATH_ROOT . '/api/includes/framework.php'));

    self::createFile(dirname(dirname(__DIR__)) . '/engaged.json', '{ "publicPath": "'. $folder . '" }');
  }
}
