<?php

/**
 * @copyright  (C) 2023 Dimitrios Grammatikogiannis
 * @license    GNU General Public License version 3 or later
 */

namespace Dgrammatiko\Module\Publicfolder\Administrator\Helper;

\defined('_JEXEC') || die();

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

class PublicfolderHelper
{
  public static function createAjax()
  {
    if (Session::checkToken('post') && Factory::getUser()->authorise('core.admin')) {
      $input = Factory::getApplication()->input;
      // Remove the last (Windows || NIX) slash
      $folder = rtrim($input->getString('folder', ''), '/');
      $folder = rtrim($folder, '\\');

      return self::createPublicFolder($folder);
    }

    throw new \Exception('You shall not pass', 200);
  }

  private static function createSymlink($source, $dest) {
    if (!symlink($source, $dest)) {
      throw new \Exception('Unable to symlink the file: ' . str_replace(JPATH_ROOT, '', $source), 200);
    }
  }

  private static function createFile($path, $content){
    if (!file_put_contents($path, $content)) {
      throw new \Exception('Unable to create the file: ' . str_replace(JPATH_ROOT, '', $path), 200);
    }
  }

  private static function createPublicFolder($folder) {
    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
      throw new \Exception('The given directory doesn\'t exist or not accessible due to wrong permissions', 200);
    }

    // Create the required folders
    if (!mkdir($folder . '/administrator/components/com_joomlaupdate', 0755, true)
      || !mkdir($folder . '/administrator/includes', 0755, true)
      || !mkdir($folder . '/api/includes', 0755, true)
      || !mkdir($folder . '/includes', 0755)) {
      throw new \Exception('Unable to write on the given directory, check the permissions', 200);
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

    // Copy static files
    file_put_contents($folder . '/includes/incompatible.html', file_get_contents(JPATH_ROOT . '/templates/system/incompatible.html'));

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

    // Create the index.php both for root, api and administrator
    self::createFile($folder . '/index.php', file_get_contents(JPATH_ROOT . '/index.php'));
    self::createFile($folder . '/administrator/index.php', file_get_contents(JPATH_ROOT . '/index.php'));
    self::createFile($folder . '/api/index.php', file_get_contents(JPATH_ROOT . '/api/index.php'));

    $definesTemplate = <<<HTML
<?php

/**
 * @package    Joomla.Site
 *
 * @copyright  (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Defines.
define('JPATH_BASE', {{BASEFOLDER}});
define('JPATH_ROOT', JPATH_BASE);
define('JPATH_SITE', JPATH_ROOT);
define('JPATH_PUBLIC', {{PUBLICOLDER}});
define('JPATH_CONFIGURATION', JPATH_ROOT);
define('JPATH_ADMINISTRATOR', JPATH_ROOT . DIRECTORY_SEPARATOR . 'administrator');
define('JPATH_LIBRARIES', JPATH_ROOT . DIRECTORY_SEPARATOR . 'libraries');
define('JPATH_PLUGINS', JPATH_ROOT . DIRECTORY_SEPARATOR . 'plugins');
define('JPATH_INSTALLATION', JPATH_ROOT . DIRECTORY_SEPARATOR . 'installation');
define('JPATH_THEMES', JPATH_BASE . DIRECTORY_SEPARATOR . 'templates');
define('JPATH_CACHE', JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'cache');
define('JPATH_MANIFESTS', JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'manifests');
define('JPATH_API', JPATH_ROOT . DIRECTORY_SEPARATOR . 'api');
define('JPATH_CLI', JPATH_ROOT . DIRECTORY_SEPARATOR . 'cli');
define('_JDEFINES', '1');

HTML;

    // The defines files
    $definesContent = str_replace(['{{BASEFOLDER}}', '{{PUBLICFOLDER}}'], ['"' . JPATH_BASE . '"', '"' . $folder . '"'], $definesTemplate);
    self::createFile($folder . '/defines.php', $definesContent);
    self::createFile($folder . '/administrator/defines.php', $definesContent);
    self::createFile($folder . '/api/defines.php', $definesContent);

    // Populate the includes
    self::createFile($folder . '/includes/app.php', file_get_contents(JPATH_ROOT . '/includes/app.php'));
    self::createFile($folder . '/includes/framework.php', file_get_contents(JPATH_ROOT . '/includes/framework.php'));

    // Populate the administrator/includes
    self::createFile($folder . '/administrator/includes/app.php', file_get_contents(JPATH_ROOT . '/administrator/includes/app.php'));
    self::createFile($folder . '/administrator/includes/framework.php', file_get_contents(JPATH_ROOT . '/administrator/includes/framework.php'));

    // Populate the api/includes
    self::createFile($folder . '/api/includes/app.php', file_get_contents(JPATH_ROOT . '/api/includes/app.php'));
    self::createFile($folder . '/api/includes/framework.php', file_get_contents(JPATH_ROOT . '/api/includes/framework.php'));

    self::createFile(dirname(dirname(__DIR__)) . '/engaged.json', '{ "publicPath": "'. $folder . '" }');
  }
}
