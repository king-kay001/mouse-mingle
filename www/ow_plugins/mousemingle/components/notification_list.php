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

class MOUSE_CMP_NotificationList extends OW_Component
{
    public function __construct( $userId, $perPage = 10, $beforeTime = null, $ignoreIds = array() )
    {
        parent::__construct();

        $beforeTime = !empty($beforeTime) ? $beforeTime : time();

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $offset = ($page -1) * $perPage;
        $itemCount = NOTIFICATIONS_BOL_NotificationDao::getInstance()->findNotificationCount($userId);

        // Add pagination
        $pagerCmp = new BASE_CMP_Paging($page, ceil($itemCount / $perPage), 5);
        $this->addComponent('pagerCmp', $pagerCmp);

        $notifications = MOUSE_BOL_NotificationDao::getInstance()->getNotificationList($userId, $beforeTime, $offset, $perPage);

        $notificationList = [];
        $notificationIds = [];

        foreach ( $notifications as $notification )
        {
            $notificationData = $notification->getData();
            
            $itemEvent = new OW_Event('notifications.on_item_render', array(
                'key' => 'notification_' . $notification->id,
                'entityType' => $notification->entityType,
                'entityId' => $notification->entityId,
                'pluginKey' => $notification->pluginKey,
                'userId' => $notification->userId,
                'viewed' => (bool) $notification->viewed,
                'data' => $notificationData
            ), $notificationData);

            OW::getEventManager()->trigger($itemEvent);

            $item = $itemEvent->getData();

            if ( empty($item) )
            {
                continue;
            }
            
            $notificationIds[] = $notification->id;
            $notificationList[$notification->id] = $item;
        }
        
        $this->assign('notificationList', $notificationList);
        
        NOTIFICATIONS_BOL_Service::getInstance()->markNotificationsViewedByIds($notificationIds);
    }
}