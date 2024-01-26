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

class MOUSE_CMP_UserList extends OW_Component
{
    const EVENT_LOAD_USERLIST = 'mouse.load_userlist';
    const EVENT_USERLIST_COUNT = 'mouse.userlist_count';
    
    const LIST_TYPE_SEARCH = 'search';
    const LIST_TYPE_VIEWS = 'views';
    const LIST_TYPE_LIKES = 'likes';
    const LIST_TYPE_BOOKMARKS = 'bookmarks';
    const LIST_TYPE_MATCHES = 'matches';

    /**
     * @var bool
     */
    protected $showOnline = true;
    /**
     * @var array
     */
    protected $list = array();
    /**
     * @var string
     */
    protected $listType;

    public function __construct( $listType, $page, $limit, $showOnline = true )
    {
        parent::__construct();

        $this->listType = $listType;
        $this->showOnline = $showOnline;

        $event = OW::getEventManager()->trigger( new OW_Event(self::EVENT_LOAD_USERLIST, [
            'listType' => $listType,
            'page' => $page,
            'limit' => $limit,
            'showOnline' => $showOnline,
        ], []));

        $this->list = $event->getData();
        $this->showOnline = $showOnline;
        $this->assign('listType', $listType);

        // OW::getDocument()->addOnloadScript(UTIL_JsGenerator::composeJsString('console.log({$list})', ['list' => $this->list]));

        $this->setTemplate(OW::getPluginManager()->getPlugin('mouse')->getCmpViewDir() . 'user_list.html');
    }

    protected function process( $list, $showOnline )
    {
        $service = BOL_UserService::getInstance();
        $idList = array();
        $userList = array();

        foreach ( $list as $dto )
        {
            $dto = (array) $dto;
            $userList[] = array('dto' => $dto);
            $idList[] = (int) $dto['id'];
        }

        $avatars = array();
        $usernameList = array();
        $displayNameList = array();
        $onlineInfo = array();
        $questionList = array();

        if ( !empty($idList) )
        {
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList);
            $event = new OW_Event('bookmarks.is_mark', array(), $avatars);
            OW::getEventManager()->trigger($event);
    
            if ( $event->getData() )
            {
                $avatars = $event->getData();
            }

            foreach ( $avatars as $userId => $avatarData )
            {
                $displayNameList[$userId] = isset($avatarData['title']) ? $avatarData['title'] : '';

                $avatars[$userId] = array_merge($avatarData, MOUSE_BOL_UserService::getInstance()->getUserInfo($userId));

            }


            $usernameList = $service->getUserNamesForList($idList);

            if ( $showOnline )
            {
                $onlineInfo = $service->findOnlineStatusForUserList($idList);
            }
        }

        $showPresenceList = array();

        $ownerIdList = array();

        foreach ( $onlineInfo as $userId => $isOnline )
        {
            $ownerIdList[$userId] = $userId;
        }

        $eventParams = array(
                'action' => 'base_view_my_presence_on_site',
                'ownerIdList' => $ownerIdList,
                'viewerId' => OW::getUser()->getId()
            );

        $permissions = OW::getEventManager()->getInstance()->call('privacy_check_permission_for_user_list', $eventParams);

        foreach ( $onlineInfo as $userId => $isOnline )
        {
            // Check privacy permissions
            if ( isset($permissions[$userId]['blocked']) && $permissions[$userId]['blocked'] == true )
            {
                $showPresenceList[$userId] = false;
                continue;
            }

            $showPresenceList[$userId] = true;
        }

        $contextMenuList = array();
        
        foreach ( $idList as $uid )
        {
            $contextMenu = $this->getContextMenu($uid);
            if ( $contextMenu )
            {
                $contextMenuList[$uid] = $contextMenu->render();
            }
            else
            {
                $contextMenuList[$uid] = null;
            }
        }


        $fields = array();

        $this->assign('contextMenuList', $contextMenuList);

        $this->assign('fields', $this->getFields($idList));
        $this->assign('questionList', $questionList);
        $this->assign('usernameList', $usernameList);
        $this->assign('avatars', $avatars);
        $this->assign('displayNameList', $displayNameList);
        $this->assign('onlineInfo', $onlineInfo);
        $this->assign('showPresenceList', $showPresenceList);
        $this->assign('list', $userList);

        $viewerId = OW::getUser()->getId();

        if( $this->listType == self::LIST_TYPE_VIEWS && OW::getPluginManager()->isPluginActive('ocsguests'))
        {
            OCSGUESTS_BOL_Service::getInstance()->setViewedStatusByGuestIds($viewerId, $idList);
        }

        if( $this->listType == self::LIST_TYPE_LIKES )
        {
            // To do: create a database table to hold viewed like ids
            // Add $idList to the table
            MOUSE_BOL_Service::getInstance()->markViewedLikeIdList($viewerId, $idList);
        }
    }

    public function getFields( $userIdList )
    {
        return [];
    }

    public function getContextMenu( $userId )
    {
        return null;
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->process($this->list, $this->showOnline);
    }
}