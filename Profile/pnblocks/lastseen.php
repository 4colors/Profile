<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 */

/**
 * initialise block
 *
 * @author       The Zikula Development Team
 */
function Profile_lastseenblock_init()
{
    // Security
    SecurityUtil::registerPermissionSchema('Profile:LastSeenblock:', 'Block title::');
}

/**
 * get information on block
 *
 * @author       The Zikula Development Team
 * @return       array       The block information
 */
function Profile_lastseenblock_info()
{
    $dom = ZLanguage::getModuleDomain('Profile');

    return array('module'          => 'Profile',
                 'text_type'       => DataUtil::formatForDisplay(__('Recent visitors', $dom)),
                 'text_type_long'  => DataUtil::formatForDisplay(__('Show registered users having visited the site recently', $dom)),
                 'allow_multiple'  => true,
                 'form_content'    => false,
                 'form_refresh'    => false,
                 'show_preview'    => true,
                 'admin_tableless' => true);
}

/**
 * display block
 *
 * @author       The Zikula Development Team
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the rendered bock
 */
function Profile_lastseenblock_display($blockinfo)
{
    // Check if the Profile module is available or saving of login dates are disabled
    if (!pnModAvailable('Profile') || !pnModGetVar('Users', 'savelastlogindate')) {
        return false;
    }

    // Security check
    if (!SecurityUtil::checkPermission('Profile:LastSeenblock:', "$blockinfo[title]::", ACCESS_READ)) {
        return false;
    }

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    $render = & pnRender::getInstance('Profile', false);

    // get last x logged in user id's
    $users = pnModAPIFunc('Profile', 'memberslist', 'getall',
                          array('sortby'    => 'lastlogin',
                                'numitems'  => $vars['amount'],
                                'sortorder' => 'DESC'));

    $render->assign('users', $users);

    $blockinfo['content'] = $render->fetch('profile_block_lastseen.htm');

    return pnBlockThemeBlock($blockinfo);
}

/**
 * modify block settings
 *
 * @author       The Zikula Development Team
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the bock form
 */
function Profile_lastseenblock_modify($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (empty($vars['amount'])) {
        $vars['amount'] = 5;
    }

    // Create output object
    $render = & pnRender::getInstance('Profile', false);

    // assign the approriate values
    $render->assign('amount', $vars['amount']);
    $render->assign('savelastlogindate', pnModGetVar('Users', 'savelastlogindate'));

    // Return the output that has been generated by this function
    return $render->fetch('profile_block_lastseen_modify.htm');
}

/**
 * update block settings
 *
 * @author       The Zikula Development Team
 * @param        array       $blockinfo     a blockinfo structure
 * @return       $blockinfo  the modified blockinfo structure
 */
function Profile_lastseenblock_update($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // alter the corresponding variable
    $vars['amount'] = (int)FormUtil::getPassedValue('amount', null, 'REQUEST');

    // write back the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $render = & pnRender::getInstance('Profile');
    $render->clear_cache('profile_block_lastseen.htm');

    return $blockinfo;
}
