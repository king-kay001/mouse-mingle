<?php

/**
 * This software is intended for use with Skadate Software https://mouse.com/ and is a proprietary licensed product. 
 * For more information see License.txt in the plugin folder.

 * ---
 * Copyright (c) 2023, Peatech LLC
 * All rights reserved.
 * dev@peatechllc.com.

 * Redistribution and use in source and binary forms, with or without modification, are not permitted provided.

 * This plugin should be bought from the developer. For details contact dev@peatechllc.com.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Membership subscribe page controller.
 *
 * @package ow_plugins.mouse.controllers
 * @since 1.0.0
 */

class MOUSE_CTRL_Subscribe extends MEMBERSHIP_CTRL_Subscribe
{
    protected $menu;
    protected $mTypes;
    /**
     * @var BOL_AuthorizationService
     */
    private $authService;
    /**
     * @var MEMBERSHIP_BOL_MembershipService
     */
    private $membershipService;

    public function __construct()
    {
        parent::__construct();

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        
        $this->authService = BOL_AuthorizationService::getInstance();
        $this->membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();

        $this->menu = $this->getMenu();

        // collecting membership list
        $event = OW::getEventManager()->trigger( new OW_Event( MOUSE_CLASS_EventHandler::EVENT_GET_MEMBERSHIPS, array()));
        $this->mTypes = $event->getData();
        
        $gateways = BOL_BillingService::getInstance()->getActiveGatewaysList();
        $this->assign('gatewaysActive', (bool) $gateways);

        $topContent = "";

        if( OW::getLanguage()->valueExist('mekirim', 'top_content') )
        {
            $topContent = OW::getLanguage()->text('mekirim', 'top_content');
        }

        $this->assign('topContent', $topContent);

        $form = new MEMBERSHIP_CLASS_SubscribeForm();
        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $form->process();
        }

        $this->setDocumentKey('mekirim-upgrade');
    }

    public function upgrade( $params )
    {
        $lang = OW::getLanguage();

        $membershipId = isset($params['id']) ? $params['id'] : null;

        if( empty($membershipId) && !empty( $this->mTypes ) ) 
        {
            $membershipId = $this->mTypes[0]->id;
        }

        if( !empty( $membership = $this->membershipService->findTypeById( $membershipId ) ) )
        {
            $userId = OW::getUser()->getId();

            $exclude = $this->membershipService->getUserTrialPlansUsage($userId);

            $mPlans = $this->membershipService->getTypePlanList($exclude);

            $membership->title = $lang->text('mekirim', 'membership_title', [
                'siteName' => OW::getConfig()->getValue('base', 'site_name'),
                'name' => $this->membershipService->getMembershipTitle($membership->roleId)
            ]);

            $membership->current = in_array($membership->roleId, $this->getUserRoleIds());
            $membership->plans = isset($mPlans[$membershipId]) ? $mPlans[$membershipId] : array();

            $planCount = 0;
            $prices = array();
            $discounts = array();

            foreach( $membership->plans as $plan )
            {
                $planDto = $plan['dto'];
                $plan['dto']->title = $this->membershipService->getMembershipTitle($membership->roleId);

                if( $planDto->periodUnits == 'days')
                    $prices[$planDto->id] = floatval( $planDto->price ) / intval($planDto->period);
                else
                    $prices[$planDto->id] = floatval( $planDto->price ) / (30 * intval($planDto->period));

                $planCount++;
            }
            
            $maxPrice = floatval(max($prices));

            foreach( $prices as $plan => $unitPrice )
            {
                $dicountPrice = $maxPrice - $unitPrice;
                $discount = ($dicountPrice / $maxPrice) * 100;
    
                $discounts[$plan] = ceil($discount);
            }

            $selectedItem = array_keys($discounts)[floor(count($discounts)/2)];

            $this->assign('discounts', $discounts);
            $this->assign('selectedItem', $selectedItem);

            $rowCount = $planCount > 1 ? $planCount : 1;
            $this->assign('plansRows', (100 / $rowCount));
        }

        $this->assign('membership', $membership);
    }

    public function index()
    {
        $lang = OW::getLanguage();

        $this->setPageHeading($lang->text('membership', 'subscribe_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_user');

        if( !empty( $this->mTypes ) ) 
        {
            $membershipId = $this->mTypes[0]->id;
        }

        $userId = OW::getUser()->getId();

        $exclude = $this->membershipService->getUserTrialPlansUsage($userId);

        $mPlans = $this->membershipService->getTypePlanList($exclude);

        if( !empty( $membership = $this->membershipService->findTypeById( $membershipId ) ) )
        {
            $membership->title = $lang->text('mekirim', 'membership_title', [
                'siteName' => OW::getConfig()->getValue('base', 'site_name'),
                'name' => $this->membershipService->getMembershipTitle($membership->roleId)
            ]);            
        }

        $this->assign('membership', $membership);

        $planList = array();

        $planCount = 0;
        $prices = array();
        $discounts = array();

        foreach( $this->mTypes as $membership )
        {
            $mId = $membership->id;

            foreach( $mPlans[$mId] as $plan )
            {
                $planDto = $plan['dto'];

                if( $planDto->periodUnits == 'days')
                    $prices[$planDto->id] = floatval( $planDto->price ) / intval($planDto->period);
                else
                    $prices[$planDto->id] = floatval( $planDto->price ) / (30 * intval($planDto->period));

                $plan['dto']->title = $this->membershipService->getMembershipTitle($membership->roleId);
                $plan['dto']->current = in_array($membership->roleId, $this->getUserRoleIds());

                $planCount++;

                $planList[] = $plan;
            }
        }

        $rowCount = $planCount > 1 ? $planCount : 1;
        $this->assign('plansRows', (100 / $rowCount));
        $this->assign('planList', $planList);

        $maxPrice = floatval(max($prices));

        foreach( $prices as $plan => $unitPrice )
        {
            $dicountPrice = $maxPrice - $unitPrice;
            $discount = ($dicountPrice / $maxPrice) * 100;

            $discounts[$plan] = ceil($discount);
        }

        $selectedItem = array_keys($discounts)[floor(count($discounts)/2)];

        $this->assign('discounts', $discounts);
        $this->assign('selectedItem', $selectedItem);
    }

    private function getUserRoleIds()
    {
        /* @var $defaultRole BOL_AuthorizationRole */
        $defaultRole = $this->authService->getDefaultRole();

        $userMembership = $this->membershipService->getUserMembership(OW::getUser()->getId());
        $userRoleIds = array($defaultRole->id);
        
        if ( $userMembership )
        {
            $type = $this->membershipService->findTypeById($userMembership->typeId);
            if ( $type )
            {
                $userRoleIds[] = $type->roleId;
                $this->assign('currentTitle', $this->membershipService->getMembershipTitle($type->roleId));
            }

            $this->assign('current', $userMembership);
        }

        return $userRoleIds;
    }
    
    public function onBeforeRender()
    {
        if( !is_null($this->menu) )
        {
            if( !empty($creditMenu = $this->menu->getElement('usercredits ') ) )
            {
                $creditMenu->setOrder(count($this->menu->getMenuItems()));
            }
            
            $this->addComponent('menu', $this->menu);
        }

        parent::onBeforeRender();
    }
}