<?php
/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Controller;

use ModUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\ThemeModule\Engine\Annotation\Theme;

/**
 * Class AdminController
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * Route not needed here because this is a legacy-only method
     * 
     * The default entrypoint.
     *
     * @return RedirectResponse
     */
    public function mainAction()
    {
        @trigger_error('The zikulaprofilemodule_admin_main route is deprecated. please use zikulaprofilemodule_admin_view instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_admin_view');
    }

    /**
     * @Route("")
     * @Theme("admin")
     * 
     * the default entrypoint.
     * 
     * @return RedirectResponse
     */
    public function indexAction()
    {
        @trigger_error('The zikulaprofilemodule_admin_index route is deprecated. please use zikulaprofilemodule_admin_view instead.', E_USER_DEPRECATED);

        return $this->redirectToRoute('zikulaprofilemodule_admin_view');
    }

    /**
     * @Route("/help")
     * @Theme("admin")
     * @Template
     * 
     * The Profile help page.
     *
     * @return Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function helpAction()
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        return [];
    }

    /**
     * @Route("/view/{numitems}/{startnum}", requirements={"numitems" = "\d+", "startnum" = "\d+"})
     * @Method("GET")
     * @Theme("admin")
     * @Template
     * 
     * View all items managed by this module.
     * 
     * @param integer $numitems
     * @param integer $startnum
     *
     * @return Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function viewAction($numitems = -1, $startnum = 1)
    {
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        $items = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'getall', ['startnum' => $startnum, 'numitems' => $numitems]);
        $count = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'countitems');
        $csrftoken = $this->get('zikula_core.common.csrf_token_handler')->generate();
        $x = 1;
        $duditems = [];

        foreach ($items as $item) {
            // display the proper icon and link to enable or disable the field
            switch (true) {
                // 0 <= DUD types can't be disabled
                case $item['prop_dtype'] <= 0:
                    $statusval = 1;
                    $status = [
                        'url' => '',
                        'labelClass' => 'label label-success',
                        'current' => $this->__('Active'),
                        'title' => $this->__('Required')
                    ];
                    break;
                case $item['prop_weight'] != 0:
                    $statusval = 1;
                    $status = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_deactivate', ['dudid' => $item['prop_id'], 'weight' => $item['prop_weight'], 'csrftoken' => $csrftoken]),
                        'labelClass' => 'label label-success',
                        'current' => $this->__('Active'),
                        'title' => $this->__('Deactivate')
                    ];
                    break;
                default:
                    $statusval = 0;
                    $status = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_activate', ['dudid' => $item['prop_id'], 'csrftoken' => $csrftoken]),
                        'labelClass' => 'label label-danger',
                        'current' => $this->__('Inactive'),
                        'title' => $this->__('Activate')
                    ];
            }
            // analyzes the DUD type
            switch ($item['prop_dtype']) {
                case '-2':
                    // non-editable field
                    $data_type_text = $this->__('Not editable field');
                    break;
                case '-1':
                    // Third party (non-editable)
                    $data_type_text = $this->__('Third-party (not editable)');
                    break;
                case '0':
                    // Third party (mandatory)
                    $data_type_text = $this->__('Third-party') . ($item['prop_required'] ? ', ' . $this->__('Required') : '');
                    break;
                default:
                case '1':
                    // Normal property
                    $data_type_text = $this->__('Normal') . ($item['prop_required'] ? ', ' . $this->__('Required') : '');
                    break;
                case '2':
                    // Third party (normal field)
                    $data_type_text = $this->__('Third-party') . ($item['prop_required'] ? ', ' . $this->__('Required') : '');
                    break;
            }

            // Options for the item.
            $options = [];
            $permissionInstance = $item['prop_label'] . '::' . $item['prop_id'];
            if ($this->hasPermission('ZikulaProfileModule::item', $permissionInstance, ACCESS_EDIT)) {
                $options[] = [
                    'url' => $this->get('router')->generate('zikulaprofilemodule_admin_edit', ['dudid' => $item['prop_id']]),
                    'class' => '',
                    'iconClass' => 'fa fa-pencil fa-lg',
                    'title' => $this->__('Edit')
                ];
                if ($item['prop_weight'] > 1) {
                    $options[] = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_decreaseweight', ['dudid' => $item['prop_id']]),
                        'class' => 'profile_up',
                        'iconClass' => 'fa fa-arrow-up fa-lg',
                        'title' => $this->__('Up')
                    ];
                }
                if ($x < $count) {
                    $options[] = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_increaseweight', ['dudid' => $item['prop_id']]),
                        'class' => 'profile_down',
                        'iconClass' => 'fa fa-arrow-down fa-lg',
                        'title' => $this->__('Down')
                    ];
                }
                if ($this->hasPermission('ZikulaProfileModule::item', $permissionInstance, ACCESS_DELETE) && $item['prop_dtype'] > 0) {
                    $options[] = [
                        'url' => $this->get('router')->generate('zikulaprofilemodule_admin_delete', ['dudid' => $item['prop_id']]),
                        'class' => '', 'title' => $this->__('Delete'),
                        'iconClass' => 'fa fa-trash-o fa-lg text-danger'
                    ];
                }
            }

            $item['status'] = $status;
            $item['statusval'] = $statusval;
            $item['options'] = $options;
            $item['dtype'] = $data_type_text;
            $item['prop_fieldset'] = (isset($item['prop_fieldset']) && !empty($item['prop_fieldset'])) ? $item['prop_fieldset'] : $this->__('User Information');
            $duditems[] = $item;
            $x++;
        }

        return [
            'startNum' => $startnum,
            'dudItems' => $duditems,
            'pager' => [
                'amountOfItems' => $count,
                'itemsPerPage' => $numitems
            ]
        ]
    }

    /**
     * @Route("/modify")
     * @Method("POST")
     * @Theme("admin")
     * 
     * Create the dud - process the edit form.
     * 
     * @param Request $request
     *
     * Parameters passed via POST:
     * ----------------------------------------------------
     * integer dudid         (if editing) the property id
     * string  label         The name
     * string  attributename The attribute name
     * numeric required      0 if not required, 1 if required.
     * numeric viewby        Viewable-by option; 0 thru 3, everyone, registered users, admins and account owners, admin only.
     * numeric displaytype   Display type; 0 thru 7.
     * array   listoptions   If the display type is a list, then the options to display in the list.
     * string  note          Note for the item.
     * string  fieldset      The fieldset to group the item.
     *
     * @return RedirectResponse
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function modifyAction(Request $request)
    {
        // Security check
        if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADD)) {
            throw new AccessDeniedException();
        }
    
        // Get parameters from whatever input we need.
        $dudid = (int)$request->request->get('dudid', 0);
        $label = $request->request->get('label', null);
        $attrname = $request->request->get('attributename', null);
        $required = $request->request->get('required', null);
        $viewby = $request->request->get('viewby', null);
        $displaytype = $request->request->get('displaytype', null);
        $listoptions = $request->request->get('listoptions', null);
        $note = $request->request->get('note', null);
        $fieldset = $request->request->get('fieldset', null);
        $pattern = $request->request->get('pattern', null);
    
        // Validates and check if empty or already existing...
        if (empty($label)) {
            $this->addFlash('error', $this->__('Error! The item must have a label. An example of a recommended label is: \'_MYDUDLABEL\'.'));

            return $this->redirectToRoute('zikulaprofilemodule_admin_view');
        }
        if (empty($dudid) && empty($attrname)) {
            $this->addFlash('error', $this->__('Error! The item must have an attribute name. An example of an acceptable name is: \'mydudfield\'.'));

            return $this->redirectToRoute('zikulaprofilemodule_admin_view');
        }
        //@todo The check needs to occur for both the label and fieldset.
        //if (ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['proplabel' => $label, 'propfieldset' => $fieldset])) {
        //    $this->addFlash('error', $this->__('Error! There is already a label with this name.'));
        //    return $this->redirectToRoute('zikulaprofilemodule_admin_view');
        //}
        if (isset($attrname) && ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propattribute' => $attrname])) {
            $this->addFlash('error', $this->__('Error! There is already an attribute name with this naming.'));

            return $this->redirectToRoute('zikulaprofilemodule_admin_view');
        }
        $filteredlabel = $label;

        $parameters = [
            'dudid' => $dudid,
            'label' => $filteredlabel,
            'attribute_name' => $attrname,
            'required' => $required,
            'viewby' => $viewby,
            'dtype' => 1,
            'displaytype' => $displaytype,
            'listoptions' => $listoptions,
            'note' => $note,
            'fieldset' => $fieldset,
            'pattern' => $pattern
        ];
        if (empty($dudid)) {
            $dudid = ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'create', $parameters);
            $successMessage = $this->__('Done! Created new personal info item.');
        } else {
            $dudid = ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'update', $parameters);
            $successMessage = $this->__('Done! Saved your changes.');
        }
        if ($dudid != false) {
            // Success
            $this->addFlash('status', $successMessage);
        }

        return $this->redirectToRoute('zikulaprofilemodule_admin_view');
    }

    /**
     * @Route("/edit/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     * @Theme("admin")
     *
     * Show form to create or modify a dynamic user data item.
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be modified.
     *
     * @return Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function editAction(Request $request, $dudid = 0)
    {
        if (empty($dudid)) {
            if (!$this->hasPermission('ZikulaProfileModule::', '::', ACCESS_ADD)) {
                throw new AccessDeniedException();
            }
        }

        $templateParameters = [
            'displaytypes' => [
                0 => $this->__('Text box'),
                1 => $this->__('Text area'),
                2 => $this->__('Checkbox'),
                3 => $this->__('Radio button'),
                4 => $this->__('Dropdown list'),
                5 => $this->__('Date'),
                7 => $this->__('Multiple checkbox set')
            ],
            'requiredoptions' => [
                0 => $this->__('No'),
                1 => $this->__('Yes')
            ],
            'viewbyoptions' => [
                0 => $this->__('Everyone'),
                1 => $this->__('Registered users only'),
                2 => $this->__('Admins and account owner only'),
                3 => $this->__('Admins only')
            ]
        ];

        if (!empty($dudid)) {
            $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
            if ($item == false) {
                throw new NotFoundHttpException($this->__('Error! No such personal info item found.'));
            }

            // Security check
            if (!$this->hasPermission('ZikulaProfileModule::item', $item['prop_label'] . '::' . $dudid, ACCESS_EDIT)) {
                throw new AccessDeniedException();
            }

            // backward check to remove any 1.4- forbidden char in listoptions 10 = New Line /n and 13 = Carriage Return /r
            $item['prop_listoptions'] = str_replace(chr(10), '', str_replace(chr(13), '', $item['prop_listoptions']));
            $item['prop_fieldset'] = (isset($item['prop_fieldset']) && !empty($item['prop_fieldset'])) ? $item['prop_fieldset'] : $this->__('User Information');
            $item['prop_listoptions'] = str_replace(' ', '', $item['prop_listoptions']);

            $templateParameters['item'] = $item;
        }

        // Add a hidden variable for the item id.
        $templateParameters['dudid'] = $dudid;

        return $this->render('@ZikulaProfileModule/Admin/edit.html.twig', $templateParameters);
    }

    /**
     * @Route("/delete")
     * @Theme("admin")
     * @Template
     *
     * Delete a dud item.
     *
     * @param Request $request
     *
     * Parameters passed via GET or via POST:
     * ------------------------------------------------------------
     * int  dudid        The id of the item to be deleted.
     * bool confirmation Confirmation that this item can be deleted.
     *
     * @return RedirectResponse|Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function deleteAction(Request $request)
    {
        // Get parameters from whatever input we need.
        $dudid = (int)$request->get('dudid', null);

        // The user API function is called.
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
        if ($item == false) {
            throw new NotFoundHttpException($this->__('Error! No such personal info item found.'));

            return new Response();
        }

        // Security check
        if (!$this->hasPermission('ZikulaProfileModule::item', $item['prop_label'] . '::' . $dudid, ACCESS_DELETE)) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm('Zikula\ProfileModule\Form\DeleteDudType', $item);

        if ($form->handleRequest($request)->isValid()) {
            if (ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'delete', ['dudid' => $dudid])) {
                // Success
                $this->addFlash('status', $this->__('Done! The field has been successfully deleted.'));
            }

            return $this->redirectToRoute('zikulaprofilemodule_admin_view');
        }

        return [
            'deleteForm' => $form->createView()
        ];
    }

    /**
     * @Route("/increaseweight/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     * @Theme("admin")
     *
     * Increase weight of a dud item in the sorted list.
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be updated.
     *
     * @return RedirectResponse
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function increaseWeightAction(Request $request, $dudid)
    {
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
        if ($item == false) {
            throw new NotFoundHttpException($this->__('Error! No such personal info item found.'));
        }

        // Security check
        if (!$this->hasPermission('ZikulaProfileModule::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        /** @var $prop \Zikula\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('ZikulaProfileModule:PropertyEntity', $dudid);
        $prop->incrementWeight();
        $this->entityManager->flush();

        return $this->redirectToRoute('zikulaprofilemodule_admin_view');
    }

    /**
     * @Route("/decreaseweight/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     * @Theme("admin")
     *
     * Decrease weight of a dud item in the sorted list.
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be updated.
     *
     * @return RedirectResponse|Response
     *
     * @throws AccessDeniedException on failed permission check
     */
    public function decreaseWeightAction(Request $request, $dudid)
    {
        $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propid' => $dudid]);
        if ($item == false) {
            throw new NotFoundHttpException($this->__('Error! No such personal info item found.'));
        }

        // Security check
        if (!$this->hasPermission('ZikulaProfileModule::item', "{$item['prop_label']}::{$item['prop_id']}", ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        if ($item['prop_weight'] <= 1) {
            $this->addFlash('error', $this->__('Error! You cannot decrease the weight of this account property.'));

            return new Response();
        }

        /** @var $prop \Zikula\ProfileModule\Entity\PropertyEntity */
        $prop = $this->entityManager->find('ZikulaProfileModule:PropertyEntity', $dudid);
        $prop->decrementWeight();
        $this->entityManager->flush();

        return $this->redirectToRoute('zikulaprofilemodule_admin_view');
    }

    /**
     * @Route("/activate/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     * @Theme("admin")
     *
     * Process item activation request
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be updated.
     *
     * @return RedirectResponse
     */
    public function activateAction(Request $request, $dudid)
    {
        $this->get('zikula_core.common.csrf_token_handler')->validate($request->query->get('csrftoken');

        // The API function is called.
        if (ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'activate', ['dudid' => $dudid])) {
            // Success
            $this->addFlash('status', $this->__('Done! Saved your changes.'));
        }

        return $this->redirectToRoute('zikulaprofilemodule_admin_view');
    }

    /**
     * @Route("/deactivate/{dudid}", requirements={"dudid" = "\d+"})
     * @Method("GET")
     * @Theme("admin")
     *
     * Process item deactivation request
     *
     * @param Request $request
     * @param integer $dudid The id of the item to be updated.
     *
     * @return RedirectResponse
     */
    public function deactivateAction(Request $request, $dudid)
    {
        $this->get('zikula_core.common.csrf_token_handler')->validate($request->query->get('csrftoken');

        // The API function is called.
        if (ModUtil::apiFunc('ZikulaProfileModule', 'admin', 'deactivate', ['dudid' => $dudid])) {
            // Success
            $this->addFlash('status', $this->__('Done! Saved your changes.'));
        }

        return $this->redirectToRoute('zikulaprofilemodule_admin_view');
    }
}
