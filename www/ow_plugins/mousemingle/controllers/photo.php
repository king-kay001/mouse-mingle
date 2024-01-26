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

class MOUSE_CTRL_Photo extends PHOTO_CTRL_Photo
{
    public $photoService;
    public $photoAlbumService;

    public function __construct()
    {
        parent::__construct();
    }

    public function userPhotos($params)
    {
        parent::userPhotos($params);
    }

    public function viewList($params)
    {
        parent::viewList($params);
    }

    public function init()
    {
        parent::init();
        
        $hadler = OW::getRequestHandler()->getHandlerAttributes();
        
        if ( OW::getUser()->isAuthenticated() )
        {
            switch ( $hadler[OW_RequestHandler::ATTRS_KEY_ACTION] )
            {
                case 'view':
                    $ownerMode = $this->photoService->findPhotoOwner($hadler[OW_RequestHandler::ATTRS_KEY_VARLIST]['id']) == OW::getUser()->getId();
                    $contentOwner = $this->photoService->findPhotoOwner((int)$hadler[OW_RequestHandler::ATTRS_KEY_VARLIST]['id']);
                    break;
                case 'getFloatbox':
                    $ownerMode = $this->photoService->findPhotoOwner($_POST['photoId']) == OW::getUser()->getId();
                    break;
                case 'userAlbums':
                case 'userPhotos':
                    $ownerMode = $hadler[OW_RequestHandler::ATTRS_KEY_VARLIST]['user'] == OW::getUser()->getUserObject()->username;
                    $contentOwner = ($user = BOL_UserService::getInstance()->findByUsername($hadler[OW_RequestHandler::ATTRS_KEY_VARLIST]['user'])) !== NULL ? $user->id : 0;
                    break;
                case 'userAlbum':
                    $ownerMode = $this->photoAlbumService->isAlbumOwner($hadler[OW_RequestHandler::ATTRS_KEY_VARLIST]['album'], OW::getUser()->getId());
                    $contentOwner = ($album = $this->photoAlbumService->findAlbumById((int)$hadler[OW_RequestHandler::ATTRS_KEY_VARLIST]['album'])) !== NULL ? $album->userId : 0;
                    break;
                case 'ajaxResponder':
                    switch ( $_POST['ajaxFunc'] )
                    {
                        case 'getAlbumList':
                            $ownerMode = $_POST['userId'] == OW::getUser()->getId();
                            break;
                        case 'getPhotoList':
                            if ( !empty($_POST['userId']) )
                            {
                                $ownerMode = $_POST['userId'] == OW::getUser()->getId();
                            }
                            elseif ( !empty($_POST['albumId']) )
                            {
                                $albumId = (int)$_POST['albumId'];
                                $ownerMode = $this->photoAlbumService->isAlbumOwner($albumId, OW::getUser()->getId());
                            }
                            else
                            {
                                $ownerMode = false;
                            }
                            break;
                        case 'ajaxDeletePhotos':
                        case 'ajaxMoverToAlbum':
                            $ownerMode = $this->photoAlbumService->isAlbumOwner($_POST['albumId'], OW::getUser()->getId());
                            break;
                        case 'ajaxSetFeaturedStatus':
                        case 'setAsAvatar':
                            $ownerMode = $this->photoService->findPhotoOwner($_POST['entityId']) == OW::getUser()->getId();
                            break;
                        case 'ajaxDeletePhoto':
                            $photoId = (int)$_POST['entityId'];
                            $ownerId = $this->photoService->findPhotoOwner($photoId);
                            $ownerMode = $ownerId !== null && $ownerId == OW::getUser()->getId();
                            break;
                        case 'ajaxDeletePhotoAlbum':
                            $albumId = (int)$_POST['entityId'];
                            $ownerMode = $this->photoAlbumService->isAlbumOwner($albumId, OW::getUser()->getId());
                            break;
                        case 'getFloatbox':
                            $photoId = (int)$_POST['photoId'];
                            $ownerId = $this->photoService->findPhotoOwner($photoId);
                            $ownerMode = $ownerId !== null && $ownerId == OW::getUser()->getId();
                            break;
                        default:
                            $ownerMode = FALSE;
                            break;
                    }
                    break;
                case 'ajaxUpdatePhoto':
                    $ownerMode = $this->photoService->findPhotoOwner($_POST['photoId']) == OW::getUser()->getId();
                    break;
                case 'downloadPhoto':
                    $ownerMode = $this->photoService->findPhotoOwner($hadler[OW_RequestHandler::ATTRS_KEY_VARLIST]['id']) == OW::getUser()->getId();
                    break;
                case 'ajaxUpdateAlbum':
                    $ownerMode = $this->photoAlbumService->isAlbumOwner($_POST['album-id'], OW::getUser()->getId());
                    break;
                case 'ajaxCreateAlbum':
                    $ownerMode = TRUE;
                    break;
                default:
                    $ownerMode = FALSE;
                    break;
            }
        }
        else
        {
            $ownerMode = FALSE;
        }
        
        $modPermissions = OW::getUser()->isAuthorized('photo');
        $isAuthorized = OW::getUser()->isAuthorized('photo', 'view');
        
        if ( !$ownerMode && !$modPermissions && !$isAuthorized )
        {
            if ( OW::getRequest()->isAjax() )
            {
                exit(json_encode(array('result' => FALSE, 'status' => 'error', 'msg' => OW::getLanguage()->text('photo', 'auth_view_permissions'))));
            }
            else
            {
                $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                
                return;
            }
        }
        
        if ( !empty($contentOwner) && !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $contentOwner, 'viewerId' => OW::getUser()->getId());
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }
        
        if ( OW::getRequest()->isAjax() || in_array($hadler[OW_RequestHandler::ATTRS_KEY_ACTION], array('downloadPhoto', 'approve')) )
        {
            return;
        }
        
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'photo', 'photo');
        
        if ( $hadler[OW_RequestHandler::ATTRS_KEY_ACTION] != 'view' )
        {
            $this->addComponent('pageHead', OW::getClassInstance('MOUSE_CMP_PhotoPageHead', $ownerMode, !empty($album) ? $album : NULL));
        }
    }
}