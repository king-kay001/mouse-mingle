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

class MOUSE_CTRL_Profile extends OW_ActionController
{
    /**
     * @var MOUSE_BOL_UserService
     */
    private $userService;

    public function __construct()
    {
        if(! OW::getUser()->isAuthenticated())
        {
            throw new AuthenticateException();
        }

        $this->userService = MOUSE_BOL_UserService::getInstance();
        
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'base', 'main_menu_my_profile');
    }

    
    public function index($paramList)
    {
        $userService = BOL_UserService::getInstance();
        /* @var $userDao BOL_User */
        $userDto = $userService->findByUsername($paramList['username']);

        if ( $userDto === null )
        {
            throw new Redirect404Exception();
        }

        $viewerId = OW::getUser()->getId();

        /* if ( $userDto->id == $viewerId )
        {
            $this->myProfile($paramList);

            return;
        } */

        if ( !OW::getUser()->isAuthorized('base', 'view_profile') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('base', 'view_profile');
            throw new AuthorizationException($status['msg']);
        }
        
        $event = new OW_Event('privacy_check_permission', array(
            'action' => 'base_view_profile',
            'ownerId' => $userDto->id,
            'viewerId' => OW::getUser()->getId()
        ));

        try
        {
            OW::getEventManager()->getInstance()->trigger($event);
        }
        catch ( RedirectException $ex )
        {
            $exception = new RedirectException(OW::getRouter()->urlForRoute('base_user_privacy_no_permission', array('username' => $userDto->username)));

            throw $exception;
        }

        $userInfo = $this->userService->getUserInfo($userDto->id);
        $this->assign('userInfo', $userInfo);

        $displayName = $userInfo['displayName'];

        $this->setPageTitle(OW::getLanguage()->text('base', 'profile_view_title', array('username' => $displayName)));
        OW::getDocument()->setDescription(OW::getLanguage()->text('base', 'profile_view_description', array('username' => $displayName)));

        $event = new OW_Event('base.on_get_user_status', array('userId' => $userDto->id));
        OW::getEventManager()->trigger($event);
        $status = $event->getData();

        $headingSuffix = "";
        
        if ( !BOL_UserService::getInstance()->isApproved($userDto->id) )
        {
            $headingSuffix = ' <span class="ow_remark ow_small">(' . OW::getLanguage()->text("base", "pending_approval") . ')</span>';
        }
        
        if ( $status !== null )
        {
            $heading = OW::getLanguage()->text('base', 'user_page_heading_status', array('status' => $status, 'username' => $displayName));
            $this->setPageHeading($heading . $headingSuffix);
        }
        else
        {
            $this->setPageHeading(OW::getLanguage()->text('base', 'profile_view_heading', array('username' => $displayName)) . $headingSuffix);
        }

        $this->setPageHeadingIconClass('ow_ic_user');

        $this->assign('isSuspended', $userService->isSuspended($userDto->id));
        $this->assign('isAdminViewer', OW::getUser()->isAuthorized('base'));

        // $cmp = new BASE_CMP_ProfileActionToolbar($userDto->id);
        $cmp = new MOUSE_CMP_ProfileActionToolbar($userDto->id);
        $this->addComponent('profileActionToolbar', $cmp);

        $albumCmp = new MOUSE_CMP_UserPhotoAlbum($userDto->id);
        $this->addComponent('albumCmp', $albumCmp);


        $userViewCmp = new MOUSE_CMP_UserView($userDto->id);
        $this->addComponent('userViewCmp', $userViewCmp);

        $profilePhotos = new MOUSE_CMP_ProfilePhoto($userDto->id, 5, false);
        $this->addComponent('profilePhotos', $profilePhotos);

        $noImage = $this->userService->noImageUrl($userDto->id);

        $userHasPhoto = true;
        $userphotoCount = PHOTO_BOL_PhotoService::getInstance()->countUserPhotos($userDto->id);
        if($userphotoCount < 1)
        {
            $userHasPhoto = false;
        }

        $this->assign('userHasPhoto', $userHasPhoto);

        $avatar = $userInfo['avatar']['original'] ?? $userInfo['avatar']['small'] ?? $noImage;
        $this->assign('avatar', $avatar);

        $this->setDocumentKey('base_profile_page');

        $vars = BOL_SeoService::getInstance()->getUserMetaInfo($userDto);

        // set meta info
        $params = array(
            "sectionKey" => "base.users",
            "entityKey" => "userPage",
            "title" => "base+meta_title_user_page",
            "description" => "base+meta_desc_user_page",
            "keywords" => "base+meta_keywords_user_page",
            "vars" => $vars,
            "image" => BOL_AvatarService::getInstance()->getAvatarUrl($userDto->getId(), 2)
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));

        if( OW::getPluginManager()->isPluginActive('ocsguests') )
        {
            $authService = BOL_AuthorizationService::getInstance();
            $isAdmin = $authService->isActionAuthorizedForUser($viewerId, 'admin') || $authService->isActionAuthorizedForUser($viewerId, 'base');
    
            if ( $viewerId != $userDto->id && !$isAdmin )
            {
                OCSGUESTS_BOL_Service::getInstance()->trackVisit($userDto->id, $viewerId);
            }
        }
    }

    public function myProfile()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $userId = OW::getUser()->getId();
        $avatarService = BOL_AvatarService::getInstance();
        
        $userInfo = $this->userService->getUserInfo($userId);
        $this->assign('userInfo', $userInfo);

        $this->setPageTitle(OW::getLanguage()->text('base', 'my_profile_title', array('username' => $userInfo['displayName'])));
        $this->setPageHeading(OW::getLanguage()->text('base', 'my_profile_heading', array('username' => $userInfo['displayName'])));

        OW::getDocument()->setDescription(OW::getLanguage()->text('base', 'profile_view_description', array('username' => $userInfo['displayName'])));

        $event = new OW_Event('base.on_get_user_status', array('userId' => OW::getUser()->getId()));
        OW::getEventManager()->trigger($event);
        $status = $event->getData();

        if ( $status !== null )
        {
            $heading = OW::getLanguage()->text('base', 'user_page_heading_status', array('status' => $status, 'username' => $userInfo['displayName']));
            $this->setPageHeading($heading);
        }
        else
        {
            $this->setPageHeading(OW::getLanguage()->text('base', 'profile_view_heading', array('username' => $userInfo['displayName'])));
        }

        $this->setPageHeadingIconClass('ow_ic_user');

        $avatar = $avatarService->userHasAvatar( $userId)
            ? $userInfo['avatar']['original']
            : $avatarService->getDefaultAvatarUrl(2);

        $this->assign('avatar', $avatar);

        if( OW::getPluginManager()->isPluginActive('usercredits') )
        {
            $coinCount = USERCREDITS_BOL_CreditsService::getInstance()->getCreditsBalance($userId);
            $this->assign('coinCount', $coinCount);
        }

        if( OW::getPluginManager()->isPluginActive('membership') )
        {
            $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();

            $exclude = $membershipService->getUserTrialPlansUsage($userId);
            $mPlans = $membershipService->getTypePlanList($exclude);

            if( !empty($mPlans) )
            {
                $planList = [];
                $prices = [];
                $discounts = array();
    
                foreach( $mPlans as $membershipId => $plans )
                {
                    foreach( $plans as $plan )
                    {
                        $planDto = $plan['dto'];
    
                        if( $planDto->periodUnits == 'days')
                            $prices[$planDto->id] = floatval( $planDto->price ) / intval($planDto->period);
                        else
                            $prices[$planDto->id] = floatval( $planDto->price ) / (30 * intval($planDto->period));
        
                        $planList[$planDto->id] = $plan;
                    }
                }
                
                $maxPrice = floatval(max($prices));
    
                foreach( $prices as $plan => $unitPrice )
                {
                    $dicountPrice = $maxPrice - $unitPrice;
                    $discount = ($dicountPrice / $maxPrice) * 100;
        
                    $discounts[$plan] = ceil($discount);
                }
    
                $selectedPlan = array_keys($discounts)[floor(count($discounts)/2)];
    
                $this->assign('membershipPlan', $planList[$selectedPlan]);
            }
        }
    }

    public function settings(){
        // Used to show settings links do not remove
    }

    public function language()
    {
        $form = new MOUSE_CLASS_LanguageForm();
        $this->addForm($form);
        
        $form->processForm();
    }
}