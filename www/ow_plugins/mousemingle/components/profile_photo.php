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

class MOUSE_CMP_ProfilePhoto extends OW_Component
{
    /**
     * @var int
     */
    protected $userId;
    /**
     * @var int
     */
    protected $photoCount;
    /**
     * @var bool
     */
    protected $hideFloatbox = true;
    /**
     * @var bool
     */
    protected $isOwner = false;
    /**
     * @var bool
     */
    protected $isModerator;
    /**
     * @var bool
     */
    protected $isModeration;
    /**
     * @var string
     */
    protected $cmpId;
    /**
     * @var array
     */
    protected $photoList = [];

    /**
     * @var PHOTO_BOL_PhotoService
     */
    private $photoService;

    /**
     * @var BOL_AvatarService
     */
    private $avatarService;

    /**
     * Display user profile photos
     * 
     * @param int $userId
     * @param int $photoCount
     * @param bool $hideFloatbox
     */
    public function __construct( $userId, $photoCount = 5, $hideFloatbox = true )
    {
        // Hide if photo plugin is not active
        if ( !OW::getPluginManager()->isPluginActive('photo') )
        {
            $this->setVisible(false);
            return;
        }
        
        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
        $this->avatarService = BOL_AvatarService::getInstance();
        $userService = BOL_UserService::getInstance();

        $this->userId = $userId;
        $this->photoCount = $photoCount;
        $this->hideFloatbox = $hideFloatbox;

        $this->isOwner = (OW::getUser()->getId() == $this->userId);
        $this->isModerator = (OW::getUser()->isAuthorized('base') || OW::getUser()->isAdmin());
        $this->cmpId = uniqid('profile-photo-carousel-');
        $this->isModeration = ($this->isModerator && !empty($avatarDto) && $avatarDto->status == BOL_ContentService::STATUS_APPROVAL);

        $photos = $this->photoService->findPhotoListByUserId($userId, 1, $photoCount);
        $avatarDto = $this->avatarService->findByUserId($userId);

        $hasAvatar = !empty($avatarDto);
        $avatar = $this->avatarService->getAvatarUrl($userId, 2, null, false, !($this->isModeration || $this->isOwner));
        $defaultAvatar = $this->avatarService->getDefaultAvatarUrl(2);

        $this->assign('avatar', $avatar ? $avatar : $defaultAvatar);

        // add avatar to $photoList
        if( $hasAvatar )
        {
            $this->photoList[]['url'] = $avatar;
        }

        foreach($photos as $photo)
        {
            $this->photoList[]['url'] = $this->photoService
                ->getPhotoUrlByPhotoInfo($photo['id'], PHOTO_BOL_PhotoService::TYPE_MAIN, null);
        }

        // set default avatar if $photoList is empty
        if( empty($this->photoList) )
        {
            $this->photoList[]['url'] = $defaultAvatar;
            $this->hideFloatbox = true;
        }
        
        $roles = BOL_AuthorizationService::getInstance()->getRoleListOfUsers(array($userId));
        $this->assign('role', !empty($roles[$userId]) ? $roles[$userId] : null);

        $showPresence = true;
        // Check privacy permissions 
        $eventParams = array(
            'action' => 'base_view_my_presence_on_site',
            'ownerId' => $userId,
            'viewerId' => OW::getUser()->getId()
        );
        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch ( RedirectException $e )
        {
            $showPresence = false;
        }

        $this->assign('isUserOnline', ($userService->findOnlineUserById($userId) && $showPresence));

        $this->assign('photoCount', $this->photoCount);
        $this->assign('avatarSize', OW::getConfig()->getValue('base', 'avatar_big_size'));
        
        $this->assign('moderation', $this->isModeration);
        $this->assign('avatarDto', $avatarDto);

        // approve button
        if ( $this->isModeration )
        {            
            $script = ' window.avartar_arrove_request = false;
            $("#avatar-approve").click(function(){
            
                if ( window.avartar_arrove_request == true )
                {
                    return;
                }
                
                window.avartar_arrove_request = true;
                
                $.ajax({
                    "type": "POST",
                    "url": '.json_encode(OW::getRouter()->urlFor('BASE_CTRL_Avatar', 'ajaxResponder')).',
                    "data": {
                        \'ajaxFunc\' : \'ajaxAvatarApprove\',
                        \'avatarId\' : '.((int)$avatarDto->id).'
                    },
                    "success": function(data){
                        if ( data.result == true )
                        {
                            if ( data.message )
                            {
                                OW.info(data.message);
                            }
                            else
                            {
                                OW.info('.json_encode(OW::getLanguage()->text('base', 'avatar_has_been_approved')).');
                            }
                            
                            $("#avatar-approve").remove();
                            $(".ow_avatar_pending_approval").hide();
                        }
                        else
                        {
                            if ( data.error )
                            {
                                OW.info(data.error);
                            }
                        }
                    },
                    "complete": function(){
                        window.avartar_arrove_request = false;
                    },
                    "dataType": "json"
                });
            }); ';

            OW::getDocument()->addOnloadScript($script);
        }
        
        OW::getLanguage()->addKeyForJs('base', 'avatar_has_been_approved');
    }

    protected function initJs()
    {
        $jsString = '
        let cmpId = $("#"+{$cmpId});
        let items = cmpId.data("items") || 1;

        if( !{$hideFloatbox} ) {
            $(cmpId).find(".user_photo img").click(function(){
                let img = $("<img>").attr("src", $(this).attr("src"));

                let photoView = new OW_FloatBox({
                    // $title: OW.getLanguageText("mekirim", "profile_photos_floatbox_title"),
                    $title: "",
                    $contents: $("<div>").addClass("ow_center").html(img),
                    width: 520
                });

                // OW.ajaxFloatBox("MOUSE_CMP_ProfilePhoto", [{$userId}],{
                //     width: 520,
                //     title: OW.getLanguageText("mekirim", "profile_photos_floatbox_title")
                // });
            }).css("cursor","pointer");
        }

        $(cmpId).find(".owl-carousel").owlCarousel({
            nav:{$showNav},
            items:items
        });
        ';
        
        if ( $this->isOwner )
        {            
            $jsString .=
            '$("#btn-avatar-change").click(function(){
                const avatarInput = $("#input_editForm_avatar");

                if(avatarInput.length) {
                    avatarInput.click();
                    return;
                }

                document.avatarFloatBox = OW.ajaxFloatBox(
                    "BASE_CMP_AvatarChange",
                    { params : { step : 1 } },
                    { width : 749, title: {$avatarChangeLabel}}
                );
            });

            OW.bind("base.avatar_cropped", function(data){
                if ( data.bigUrl != undefined ) {
                    $("#avatar_console_image").attr("src", data.bigUrl);
                }

                if ( data.modearationStatus )
                {
                    if ( data.modearationStatus != "active" )
                    {
                        $(".ow_avatar_pending_approval").show();
                    }
                    else 
                    {
                        $(".ow_avatar_pending_approval").hide();
                    }
                }
            });
            ';
        }

        OW::getDocument()->addOnloadScript( UTIL_JsGenerator::composeJsString($jsString, [
            'cmpId' => $this->cmpId,
            'hideFloatbox' => (bool) $this->hideFloatbox,
            'isOwner' => (bool) $this->isOwner,
            'showNav' => (count($this->photoList) > 2),
            'userId' => (int) $this->userId,
            'avatarChangeLabel' => OW::getLanguage()->text('base', 'avatar_change'),
            'addPhotoLabel' => OW::getLanguage()->text('photo', 'upload_photos'),
        ]));

        OW::getLanguage()->addKeyForJs('mekirim', 'profile_photo_floatbox_title');
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->assign('cmpId', $this->cmpId);
        $this->assign('user', BOL_UserService::getInstance()->findUserById($this->userId));
        $this->assign('owner', $this->isOwner);

        $photoJs = OW::getEventManager()->call('photo.getAddPhotoURL');
        $this->assign('photoJs', "{$photoJs}();");
        
        $this->assign('userPhotos', $this->photoList);
        $this->assign('isModerator', $this->isModerator);
        $this->assign('hideFloatbox', $this->hideFloatbox);

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('mouse')->getStaticCssUrl() . 'owl.carousel.min.css');
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('mouse')->getStaticCssUrl() . 'owl.theme.default.min.css');

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('mouse')->getStaticJsUrl() . 'owl.carousel.min.js');

        $this->initJs();
    }
}