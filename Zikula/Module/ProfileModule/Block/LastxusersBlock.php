<?php
/**
 * Copyright Zikula Foundation 2009 - Profile module for Zikula
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/GPLv3 (or at your option, any later version).
 * @package Profile
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Module\ProfileModule\Block;

use SecurityUtil;
use ModUtil;
use BlockUtil;

/**
 * "Last X Registered Users" block.
 */
class LastxusersBlock extends \Zikula_Controller_AbstractBlock
{
    /**
     * Initialise block.
     *
     * @return void
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema($this->name.':LastXUsersblock:', 'Block title::');
    }

    /**
     * Get information on the block.
     *
     * @return array The block information.
     */
    public function info()
    {
        return array(
            'module' => $this->name,
            'text_type' => $this->__('Last X registered users'),
            'text_type_long' => $this->__('Show last X registered users'),
            'allow_multiple' => true,
            'form_content' => false,
            'form_refresh' => false,
            'show_preview' => true,
            'admin_tableless' => true);
    }

    /**
     * Display the block.
     *
     * @param array $blockinfo A blockinfo structure.
     *
     * @return string The rendered block.
     */
    public function display($blockinfo)
    {
        // Check if the Profile module is available
        if (!ModUtil::available($this->name)) {
            return false;
        }
        // Security check
        if (!SecurityUtil::checkPermission($this->name.':LastXUsersblock:', "{$blockinfo['title']}::", ACCESS_READ)) {
            return false;
        }
        // Get variables from content block
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        $this->view->setCaching(false);
        // get last x logged in user id's
        $users = ModUtil::apiFunc($this->name, 'memberslist', 'getall', array('sortby' => 'user_regdate', 'numitems' => $vars['amount'], 'sortorder' => 'DESC'));
        $this->view->assign('users', $users);
        $blockinfo['content'] = $this->view->fetch('Block/lastxusers.tpl');
        return BlockUtil::themeBlock($blockinfo);
    }

    /**
     * Modify block settings.
     *
     * @param array $blockinfo A blockinfo structure.
     *
     * @return string The rendered block form.
     */
    public function modify($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        // Defaults
        if (empty($vars['amount'])) {
            $vars['amount'] = 5;
        }
        // Create output object
        $this->view->setCaching(false);
        // assign the approriate values
        $this->view->assign('amount', $vars['amount']);
        // Return the output that has been generated by this function
        return $this->view->fetch('Block/lastxusers_modify.tpl');
    }

    /**
     * Update block settings.
     *
     * @param array $blockinfo A blockinfo structure.
     *
     * @return array The modified blockinfo structure.
     */
    public function update($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
        // alter the corresponding variable
        $vars['amount'] = (int)$this->request->getPost()->get('amount', null);
        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
        // clear the block cache
        $this->view->clear_cache('Block/lastxusers.tpl');
        return $blockinfo;
    }

}