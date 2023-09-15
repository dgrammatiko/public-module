<?php

/**
 * @copyright  (C) 2023 Dimitrios Grammatikogiannis
 * @license    GNU General Public License version 3 or later
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') || die();

/** @var $app \Joomla\CMS\Application\CMSApplication */
$app->getDocument()
  ->getWebAssetManager()
  ->registerAndUseScript(
    'mod_publicfolder.default',
    'mod_publicfolder/admin-index.js',
    [],
    ['type' => 'module'],
    ['core']
  );

$id    = $module->id;
$data  = (object) ['publicPath' => '{{path}}', 'rootPath' => JPATH_ROOT];
$token = HTMLHelper::_('form.csrf');

if (defined('JPATH_PUBLIC') && JPATH_PUBLIC !== JPATH_ROOT) {
  $formClass = 'publicFolderForm visually-hidden';
  $resClass  = 'publicFolderResults';
  $data->publicPath = JPATH_PUBLIC;
} else {
  $formClass = 'publicFolderForm';
  $resClass  = 'publicFolderResults visually-hidden';
}
?>
<form class="d-grid gap-2 <?= $formClass; ?>">
  <div>
    <label for="inputPublicFolder-<?= $id; ?>"></label>
    <input type="text" name="public-folder" class="form-control" id="inputPublicFolder-<?= $id; ?>" placeholder="/usr/username/www/html" autocomplete="off" autocorrect="off">
  </div>
  <div class="d-grid gap-2">
    <button disabled type="button" class="btn btn-primary btn-block" data-url="<?= Uri::base(); ?>">Create Public Folder</button>
    </div>
</form>
<div class="<?= $resClass; ?>">
  <p>There is an active public folder at</p>
  <code><pre><?= $data->publicPath; ?></pre></code>
  <code><pre><?= $data->rootPath; ?></pre></code>
</div>
