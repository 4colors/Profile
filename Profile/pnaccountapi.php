<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c), Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnaccountapi.php 365 2009-11-23 16:18:55Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Profile
 * @author Mark West
 */

/**
 * Return an array of items to show in the your account panel
 *
 * @return   array   array of items, or false on failure
 */
function Profile_accountapi_getall($args)
{
    $dom = ZLanguage::getModuleDomain('Profile');

    $uname = isset($args['uname']) ? $args['uname'] : null;
    if (!$uname && pnUserloggedIn()) {
        $uname = pnUserGetVar('uname');
    }

    $items = array();

    // Create an array of links to return
    if (!empty($uname)) {
        $uid = pnUserGetIDFromName($uname);
        $items['0'] = array('url'     => pnModURL('Profile', 'user', 'view', array('uid' => $uid)),
                            'module'  => 'Profile',
                            //! account panel link
                            'title'   => __('Personal Info', $dom),
                            'icon'    => 'admin.gif');

        if (SecurityUtil::checkPermission('Profile:Members:', '::', ACCESS_READ)) {
            $items['1'] = array('url'     => pnModURL('Profile', 'user', 'viewmembers'),
                                'module'  => 'Profile',
                                'title'   => __('Members List', $dom),
                                'icon'    => 'members.gif');
        }
    }

    // Return the items
    return $items;
}