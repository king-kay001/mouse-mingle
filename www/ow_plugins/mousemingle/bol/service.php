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

class MOUSE_BOL_Service
{
    const QUESTIONS_ICON_CONFIG = 'profile_qiestions_icon';

    /**
     * @var MOUSE_BOL_Service
     */

    private static $classIntance;

    /**
     * @return MOUSE_BOL_Service
     */
    public static function getInstance()
    {
        if(self::$classIntance === null){
            self::$classIntance = new self();
        }

        return self::$classIntance;
    }

    public function getProfileQuestionIcon( $questionName )
    {
        if( empty($config = OW::getConfig()->getValue('mekirim', self::QUESTIONS_ICON_CONFIG))
        // unserialise config
        || empty($config = unserialize($config)) )
        {
            return;
        }

        if( isset($config[$questionName]) )
        {
            return $config[$questionName];
        }

        return null;
    }

    public function getNewNotificationList($userId, $beforeStamp, $offset, $limit)
    {
        return MOUSE_BOL_NotificationDao::getInstance()->getNewNotificationList($userId, $beforeStamp, $offset, $limit);
    }

    public function myBookersList($userId, $first, $count)
    {
        return MOUSE_BOL_MarkDao::getInstance()->myBookersList($userId, $first, $count);
    }

    public function myBookersListCount($userId)
    {
        return MOUSE_BOL_MarkDao::getInstance()->myBookersListCount($userId);
    }

    public function markViewedLikeIdList( $userId, $markerIdList )
    {
        if( empty($markerIdList) )
        {
            return;
        }

        foreach( $markerIdList as $markerId )
        {
            if( MOUSE_BOL_LikedViewDao::getInstance()->likeIsMarked($userId, $markerId) )
            {
                continue;
            }

            $viewed = new MOUSE_BOL_LikedView();
            $viewed->markerId = $markerId;
            $viewed->userId = $userId;

            MOUSE_BOL_LikedViewDao::getInstance()->save($viewed);
        }
    }
}