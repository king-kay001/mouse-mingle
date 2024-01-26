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

class MOUSE_CMP_UserPhotoAlbum extends OW_Component
{
    public function __construct($userId, $count = 4, $showFooter=true)
    {
        if( empty($user = BOL_UserService::getInstance()->findUserById($userId) ) )
        {
            $this->setVisible(false);
        }
        
        // privacy check
        $viewerId = OW::getUser()->getId();
        $ownerMode = $userId == $viewerId;
        $modPermissions = OW::getUser()->isAuthorized('photo');
        
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $userId, 'viewerId' => $viewerId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            
            try {
                OW::getEventManager()->trigger($event);
            }
            catch ( RedirectException $e )
            {
                $this->setVisible(false);
            }
        }

        $photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        $albums = $photoAlbumService->findUserAlbumList($userId, 1, $count);

        if(empty($albums))
        {
            $this->setVisible(false);
        }

        $event = OW::getEventManager()->trigger(
            new OW_Event('photo.albumsWidgetReady', array(), $albums)
        );

        $this->assign('albums', $event->getData());
        $this->assign('username', $user->getUsername());
        $this->assign('showFooter', $showFooter);
    }
}