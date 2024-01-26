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

class MOUSE_CLASS_EventHandler
{
    const EVENT_GET_MEMBERSHIPS = 'mouse.get_memberships';
    const EVENT_NOTIFICATION = 'mouse.notifications';

    const PING_COMMAND = 'mouse.ping_command';

    /**
     * @var MOUSE_CLASS_EventHandler
     */
    private static $classInstance;

    /**
     * @return MOUSE_CLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            try
            {
                self::$classInstance = OW::getClassInstance(static::class);
            }
            catch ( ReflectionException $ex )
            {
                self::$classInstance = new self();
            }
        }

        return self::$classInstance;
    }

    public function init()
    {
        // bind method to `class.get_instance` event
        OW::getEventManager()->bind('class.get_instance', [$this, 'onClassInstance']);

        // bind members only exceptions
		OW::getEventManager()->bind('base.members_only_exceptions', array($this, 'membersOnlyException'));

        // load console event handler
        MOUSE_CLASS_ConsoleEventHandler::getInstance()->init();

        // get membership list for upgrade
        OW::getEventManager()->bind(self::EVENT_GET_MEMBERSHIPS, array($this, 'getMembershipList'));

        // on core finalise
        OW::getEventManager()->bind(OW_EventManager::ON_FINALIZE, array($this, 'onFinalize'));

        // on users load list
        OW::getEventManager()->bind(MOUSE_CMP_UserList::EVENT_LOAD_USERLIST, array($this, 'onLoadUsers'));
        OW::getEventManager()->bind(MOUSE_CMP_UserList::EVENT_USERLIST_COUNT, array($this, 'onUserListCount'));

        // ping
        OW::getEventManager()->bind(BASE_CTRL_Ping::PING_EVENT, array($this, 'ping'));

        // bind notification data event
        OW::getEventManager()->bind(self::EVENT_NOTIFICATION, [$this, 'onNotificationData']);
    }

    /**
     * 
     * Handle `class.get_instance` event.
     * Very handy to overwrite class from other plugins
     * 
     * @param OW_Event $event
     * @return mixed
     */
    public function onClassInstance( OW_Event $event )
    {
        $params = $event->getParams();
        $arguments = isset($params['arguments']) ? $params['arguments'] : array();
        $attr = OW::getRequestHandler()->getHandlerAttributes();
        
        switch($params['className'])
        {
            // on base user controller
            case 'BASE_CTRL_User':
                $rClass = new ReflectionClass('MOUSE_CTRL_User');                
                return $event->setData($rClass->newInstanceArgs($arguments));

            // on CompleteProfile controller
            case 'BASE_CTRL_CompleteProfile':
                $rClass = new ReflectionClass('MOUSE_CTRL_CompleteProfile');                
                return $event->setData($rClass->newInstanceArgs($arguments));

            // usersearch page
            case 'USEARCH_CTRL_Search':
            case 'BASE_CTRL_UserList':
                $rClass = new ReflectionClass('MOUSE_CTRL_Usearch');
                return $event->setData($rClass->newInstanceArgs($arguments));

            // on usearch main search form class
            case 'USEARCH_CMP_SearchResultList':
                $rClass = new ReflectionClass('MYSUGAR_CMP_UsearchResultList');
                return $event->setData($rClass->newInstanceArgs($arguments));

            // on user guest page
            case 'OCSGUESTS_CTRL_List':
            case 'BOOKMARKS_CTRL_List':
            case 'MATCHMAKING_CTRL_Base':
                $rClass = new ReflectionClass('MOUSE_CTRL_Notifications');

                $listTypes = [
                    'OCSGUESTS_CTRL_List' => MOUSE_CMP_UserList::LIST_TYPE_VIEWS,
                    'BOOKMARKS_CTRL_List' => MOUSE_CMP_UserList::LIST_TYPE_BOOKMARKS,
                    'MATCHMAKING_CTRL_Base' => MOUSE_CMP_UserList::LIST_TYPE_MATCHES,
                ];

                $attr[OW_Route::DISPATCH_ATTRS_VARLIST]['type'] = $listTypes[$params['className']];

                if( $params['className'] === 'BOOKMARKS_CTRL_List')
                {
                    $attr[OW_Route::DISPATCH_ATTRS_ACTION] = 'index';
                }
        
                OW::getRequestHandler()->setHandlerAttributes($attr);

                return $event->setData($rClass->newInstanceArgs($arguments));

            // on usearch main search form class
            case 'USEARCH_CMP_SearchResultList':
                $rClass = new ReflectionClass('MYSUGAR_CMP_UsearchResultList');
                return $event->setData($rClass->newInstanceArgs($arguments));

            // on usearch main search form class
            case 'USEARCH_CLASS_MainSearchForm':
                $rClass = new ReflectionClass('MYSUGAR_CLASS_MainSearchForm');
                return $event->setData($rClass->newInstanceArgs($arguments));

            // on message page
            case 'MAILBOX_CTRL_Messages':
                $rClass = new ReflectionClass('MOUSE_CTRL_Messages');
                return $event->setData($rClass->newInstanceArgs($arguments));

            // on send gift component
            case 'VIRTUALGIFTS_CMP_SendGift':
                $rClass = new ReflectionClass('MOUSE_CMP_SendGift');
                return $event->setData($rClass->newInstanceArgs($arguments));
                
            // replace admin theme controller
            case 'ADMIN_CTRL_Theme':
                $rClass = new ReflectionClass('MOUSE_CTRL_Admin');
                return $event->setData($rClass->newInstanceArgs($arguments));
        }
    }

    public function onUserListCount( OW_Event $event )
    {
        $params = $event->getParams();

        $userId = OW::getUser()->getId();

        switch( $params['listType'] )
        {
            case MOUSE_CMP_UserList::LIST_TYPE_SEARCH:
                // get session id
                $listId = OW::getSession()->get(BOL_SearchService::SEARCH_RESULT_ID_VARIABLE);

                $event->setData(MOUSE_BOL_UserService::getInstance()->getUserListCount($listId));
            return;

            case MOUSE_CMP_UserList::LIST_TYPE_VIEWS:
                $event->setData(OCSGUESTS_BOL_Service::getInstance()->countGuestsForUser($userId));
            return;

            case MOUSE_CMP_UserList::LIST_TYPE_LIKES:
                $event->setData(MOUSE_BOL_Service::getInstance()->mybookersListCount($userId));
            return;
            
            case MOUSE_CMP_UserList::LIST_TYPE_BOOKMARKS:
                $event->setData(BOOKMARKS_BOL_Service::getInstance()->findBookmarksCount($userId));
            return;

            case MOUSE_CMP_UserList::LIST_TYPE_MATCHES:
                $event->setData(MATCHMAKING_BOL_Service::getInstance()->findMatchCount($userId));
            return;
        }
    }

    public function onLoadUsers( OW_Event $event )
    {
        $params = $event->getParams();
        $page = $params['page'];
        $limit = $params['limit'];

        $userId = OW::getUser()->getId();
        $offset = ($page - 1) * $limit;
        $perPage = (int) OW::getConfig()->getValue('base', OW::getPluginManager()->isPluginActive('skadate') ? 'users_on_page' : 'users_count_on_page');
        
        $orderType = USEARCH_BOL_Service::LIST_ORDER_LATEST_ACTIVITY;

        switch( $params['listType'] )
        {
            case MOUSE_CMP_UserList::LIST_TYPE_VIEWS:
                $guests = OCSGUESTS_BOL_Service::getInstance()->findGuestsForUser($userId, $page, $perPage);
                $guestIdList = [];

                foreach( $guests as $guest )
                {
                    $guestIdList[] = $guest->guestId;
                }

                $event->setData(BOL_UserService::getInstance()->findUserListByIdList($guestIdList));

            return;
            case MOUSE_CMP_UserList::LIST_TYPE_LIKES:
                $likeList = MOUSE_BOL_Service::getInstance()->mybookersList($userId, $page, $perPage);
                $event->setData(BOL_UserService::getInstance()->findUserListByIdList($likeList));

            return;
            case MOUSE_CMP_UserList::LIST_TYPE_BOOKMARKS:
                $bookmarkList = BOOKMARKS_BOL_Service::getInstance()->findBookmarksUserIdList($userId, $page, $perPage);
                $event->setData(BOL_UserService::getInstance()->findUserListByIdList($bookmarkList));

            return;
            case MOUSE_CMP_UserList::LIST_TYPE_MATCHES:
                $matcheList = MATCHMAKING_BOL_Service::getInstance()->findMatchList($userId, $page, $perPage);
                $matchIdList = [];

                foreach( $matcheList as $match )
                {
                    $matchIdList[] = $match['id'];
                }

                $event->setData(BOL_UserService::getInstance()->findUserListByIdList($matchIdList));

            return;
            case MOUSE_CMP_UserList::LIST_TYPE_SEARCH:
                // get session id
                $listId = OW::getSession()->get(BOL_SearchService::SEARCH_RESULT_ID_VARIABLE);

                if( !$listId )
                {
                    // set default users list
                    MOUSE_BOL_UserService::getInstance()->findUserMatches(OW::getUser()->getId());
                    $listId = OW::getSession()->get(BOL_SearchService::SEARCH_RESULT_ID_VARIABLE); 
                }
                $event->setData(MOUSE_BOL_UserService::getInstance()->getSearchResultList($listId, $orderType, $offset, $limit));

            return;
        }
    }

    public function ping( OW_Event $event )
    {
        if( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $eventParams = $event->getParams();
        $params = $eventParams['params'];

        if ($eventParams['command'] != self::PING_COMMAND)
        {
            return;
        }

        $timeStamp = $params['lastFetchTime'];
        $userService = MOUSE_BOL_UserService::getInstance();

        $event->setData($userService->getNotificationsData( $timeStamp ));

        OW::getSession()->set(MOUSE_BOL_UserService::SESSION_ACTIVITY_STAMP, $timeStamp);
    }

    public function onNotificationData( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        $lastTimeStamp = $params['lastFetchTime'];
        $userId = OW::getUser()->getId();
        $userService = MOUSE_BOL_UserService::getInstance();
        
        // add notification item
        if( OW::getPluginManager()->isPluginActive('notifications') )
        {
            $notificationService = NOTIFICATIONS_BOL_Service::getInstance();

            $notificationItem = new MOUSE_CLASS_NotificationData('notifications');
            $notificationItem->setCountAll($notificationService->findNotificationCount($userId, false));
            $notificationItem->setNewCounter($userService->countNewNotification($userId, $lastTimeStamp));
            $notificationItem->setUrl(OW::getRouter()->urlForRoute('mouse.notification'));
            
            $event->add($notificationItem->getValues());
        }

        // add ocsguests views data
        if( OW::getPluginManager()->isPluginActive('ocsguests') )
        {
            $guestCount = OCSGUESTS_BOL_Service::getInstance()->findNewGuestsCount($userId);

            $guestItem = new MOUSE_CLASS_NotificationData(MOUSE_CMP_UserList::LIST_TYPE_VIEWS);
            $guestItem->setCountAll($guestCount);
            $guestItem->setNewCounter($userService->countNewUserGuests($userId, $lastTimeStamp));
            $guestItem->setUrl(OW::getRouter()->urlForRoute('mouse.notification_listing', [
                'type' => MOUSE_CMP_UserList::LIST_TYPE_VIEWS
            ]));
    
            $event->add($guestItem->getValues());
        }

        // add bookmark likes data
        if( OW::getPluginManager()->isPluginActive('bookmarks') )
        {
            $likesCount = MOUSE_BOL_Service::getInstance()->mybookersListCount($userId);

            $guestItem = new MOUSE_CLASS_NotificationData(MOUSE_CMP_UserList::LIST_TYPE_LIKES);
            $guestItem->setCountAll($likesCount);
            $guestItem->setNewCounter($likesCount);
            $guestItem->setUrl(OW::getRouter()->urlForRoute('mouse.notification_listing', [
                'type' => MOUSE_CMP_UserList::LIST_TYPE_LIKES
            ]));
    
            $event->add($guestItem->getValues());
        }
    }

    public function getMembershipList( OW_Event $event )
    {
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();

        $accTypeName = OW::getUser()->getUserObject()->getAccountType();
        $accType = BOL_QuestionService::getInstance()->findAccountTypeByName($accTypeName);

        $mTypes = $membershipService->getTypeList($accType->id);

        foreach( $mTypes as $membership )
        {
            $membership->name = $membershipService->getMembershipTitle($membership->roleId);
        }

        $event->setData( $mTypes );
    }
	
	public function membersOnlyException( BASE_CLASS_EventCollector $event )
	{
		$event->add(array('controller' => 'ZADDYAPP_CTRL_User', 'action' => 'index'));
		$event->add(array('controller' => 'ZADDYAPP_CTRL_User', 'action' => 'main'));
		$event->add(array('controller' => 'ZADDYAPP_CTRL_Join', 'action' => 'index'));
		$event->add(array('controller' => 'ZADDYAPP_CTRL_Join', 'action' => 'joinFormSubmit'));
	}

    public function onFinalize()
    {
		$attrs = OW::getRequestHandler()->getHandlerAttributes();
		if ( is_subclass_of($attrs[OW_RequestHandler::ATTRS_KEY_CTRL], "ADMIN_CTRL_Abstract") )
		{
			return;
		}
		
		if ( !OW::getRequest()->isAjax())
		{
			$this->loadStaticFiles();
		}
    }
	
	private function loadStaticFiles()
	{	
		$document = OW::getDocument();		
        // $themeStaticUrl = OW::getThemeManager()->getCurrentTheme()->getStaticUrl();
        $pluginStaticUrl = OW::getPluginManager()->getPlugin('mouse')->getStaticUrl();

        $cachedEntitiesPostfix = OW::getConfig()->getValue('base', 'cachedEntitiesPostfix');

        // $devBaseScriptUrl = OW::getRouter()->urlFor('MOUSE_CTRL_Dev', 'staticUrl', [
        //     'type' => 'js',
        //     'file' => 'script.js',
        // ]);

        $baseScriptUrl = OW::getRequest()->buildUrlQueryString( $pluginStaticUrl . "js/script.js", [
            'name' => $cachedEntitiesPostfix
        ]);

		$document->addScript($baseScriptUrl, 'text/javascript', 1030);

        $js = UTIL_JsGenerator::newInstance();
        $js->addScript('OW.Mouse = new OW_Mouse({$params});', array(
            'params' => [
                'pingInterval' => 60000,
                'lastFetchTime' => MOUSE_BOL_UserService::getInstance()->getSessionActivityStamp(),
                'notifyUrlList' => [
                    'mailbox' => OW::getRouter()->urlForRoute('mailbox_default'),
                    'notification' => OW::getRouter()->urlForRoute('mouse.notification'),
                ]
            ]
        ));

        OW::getDocument()->addOnloadScript($js);
		
        OW::getLanguage()->addKeyForJs('virtualgifts', 'send_gift_to');
        OW::getLanguage()->addKeyForJs('mouse', 'marked_notify_message');
        OW::getLanguage()->addKeyForJs('mouse', 'unmarked_notify_message');

        OW::getLanguage()->addKeyForJs('mouse', 'notifications_notification_counter');
        OW::getLanguage()->addKeyForJs('mouse', 'likes_notification_counter');
        OW::getLanguage()->addKeyForJs('mouse', 'views_notification_counter');
        OW::getLanguage()->addKeyForJs('mouse', 'mailbox_notification_counter');
        OW::getLanguage()->addKeyForJs('mouse', 'notification_notification_counter');
	}
}