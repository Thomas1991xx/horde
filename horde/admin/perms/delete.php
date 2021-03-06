<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
$permission = 'perms';
Horde_Registry::appInit('horde');
if (!$registry->isAdmin() && 
    !$injector->getInstance('Horde_Perms')->hasPermission('horde:administration:'.$permission, $registry->getAuth(), Horde_Perms::SHOW)) {
    $registry->authenticateFailure('horde', new Horde_Exception(sprintf("Not an admin and no %s permission", $permission)));
}

/* Set up the form variables. */
$vars = Horde_Variables::getDefaultVariables();
$perms = $injector->getInstance('Horde_Perms');
$corePerms = $injector->getInstance('Horde_Core_Perms');
$perm_id = $vars->get('perm_id');
$category = $vars->get('category');
try {
    $permission = $perms->getPermissionById($perm_id);
} catch (Exception $e) {
    /* If the permission fetched is an error return to permissions list. */
    $notification->push(_("Attempt to delete a non-existent permission."), 'horde.error');
    Horde::url('admin/perms/index.php', true)->redirect();
}

/* Set up form. */
$ui = new Horde_Core_Perms_Ui($perms, $corePerms);
$ui->setVars($vars);
$ui->setupDeleteForm($permission);

if ($confirmed = $ui->validateDeleteForm($info)) {
    try {
        $result = $perms->removePermission($permission, true);
        $notification->push(sprintf(_("Successfully deleted \"%s\"."), $corePerms->getTitle($permission->getName())), 'horde.success');
        Horde::url('admin/perms/index.php', true)->redirect();
    } catch (Exception $e) {
        $notification->push(sprintf(_("Unable to delete \"%s\": %s."), $corePerms->getTitle($permission->getName()), $result->getMessage()), 'horde.error');
    }
} elseif ($confirmed === false) {
    $notification->push(sprintf(_("Permission \"%s\" not deleted."), $corePerms->getTitle($permission->getName())), 'horde.success');
    Horde::url('admin/perms/index.php', true)->redirect();
}

$title = _("Permissions Administration");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

/* Render the form and tree. */
$ui->renderForm('delete.php');
echo '<br />';
$ui->renderTree($perm_id);

require HORDE_TEMPLATES . '/common-footer.inc';
