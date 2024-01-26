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

class MOUSE_CMP_UserPhotoAlbumPhotos extends OW_Component
{
    public function __construct($albumId, $showFooter = true)
    {
        parent::__construct();
        
        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);

        if( empty($album) )
        {
            $this->setVisible(false);

            return;
        }

        $isOwner = (OW::getUser()->getId() == $album->userId);
        $isMarked = MOUSE_BOL_UserService::getInstance()->isUserMarked($album->userId);

        $this->assign('isOwner', $isOwner);
        $this->assign('isMarked', $isMarked);
        $this->assign('ownerId', $album->userId);

        $photos = PHOTO_BOL_PhotoService::getInstance()->findPhotoListByAlbumId($albumId, 1, 1000);

        $this->assign('albumPhotos', $photos);
        $this->assign('showFooter', $showFooter);
        
        OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('mouse')->getStaticCssUrl() . 'owl.carousel.min.css' );
        OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('mouse')->getStaticCssUrl() . 'owl.theme.default.min.css' );

        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('mouse')->getStaticJsUrl() . 'owl.carousel.min.js' );

        OW::getDocument()->addOnloadScript('$(document).ready(function(){
            $(".ow_album_carousel").owlCarousel({
                nav:true,
                loop:true,
                margin:10,
                responsiveClass:true,
                responsive:{
                    0:{
                        items:1,
                        nav:true
                    }
                }
            });
          });');
    }

}