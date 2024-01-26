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

class MOUSE_BOL_UserService
{
    const SESSION_MATCH_USER_ID = 'mouse.session_match_user';
    const SESSION_ACTIVITY_STAMP = 'mouse.session_activity_stamp';

    const PREFERENCE_KEY_ALERT_INFO = 'mekirim_lastalert_info';

    /**
     * @var MOUSE_BOL_UserDao
     */
    protected $userDao;

    public static $classInstance;

    /**
     * @return MOUSE_BOL_UserService
     */
    public static function getInstance()
    {
        if(self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

   /**
    *
    *  @var BOL_QuestionService
    */
    private $questionService;

    private $searchDao;

    public function __construct( )
    {
        $this->questionService = BOL_QuestionService::getInstance();
        $this->searchDao = USEARCH_BOL_SearchDao::getInstance();
        $this->userDao = MOUSE_BOL_UserDao::getInstance();
    }

    public function getLastAlertInfo( $userId, $key = null )
    {
        $alertInfo = BOL_PreferenceService::getInstance()->getPreferenceValue('matchmaking_lastmatch_userid', $userId );
    }

    public function updateLastAlertInfo( $userId )
    {
        $preferenceKey = 'mekirim_lastalert_info';

        if (empty($prefeference = BOL_PreferenceService::getInstance()->findPreference($preferenceKey)))
        {
            $prefeference = new BOL_Preference();
            $prefeference->key = $preferenceKey;
            $prefeference->defaultValue = 0;
            $prefeference->sectionName = 'mekirim';
            $prefeference->sortOrder = 0;
            BOL_PreferenceService::getInstance()->savePreference($prefeference);
        }

        $alertInfo = [
            'mailbox' => 0,
            'like' => 0,
            'view' => 0,
            'notification' => 0,
        ];

        if( $userId )
        {
            BOL_PreferenceService::getInstance()->savePreferenceValue($preferenceKey, json_encode($alertInfo), $userId);
        }
    }

    public function getSessionActivityStamp()
    {
        if( $activityStamp = OW::getSession()->get(self::SESSION_ACTIVITY_STAMP) )
        {
            return $activityStamp;
        }

        $activityStamp = OW::getUser()->isAuthenticated() ? OW::getUser()->getUserObject()->getActivityStamp() : time();

        OW::getSession()->set(self::SESSION_ACTIVITY_STAMP, $activityStamp);

        return OW::getSession()->get(self::SESSION_ACTIVITY_STAMP);
    }

    /**
     * Count new user guests
     *
     * @param int $userId
     * @param int $afterStamp
     *
     * @return int
     */
    public function countNewUserGuests( $userId, $afterStamp )
    {
        $example = new OW_Example();

        $example->andFieldEqual('guestId', $userId);
        $example->andFieldGreaterThan('visitTimestamp', $afterStamp);

        return OCSGUESTS_BOL_GuestDao::getInstance()->countByExample($example);
    }

    /**
     * Count new user notifications
     *
     * @param int $userId
     * @param int $afterStamp
     *
     * @return int
     */
    public function countNewNotification( $userId, $afterStamp )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('viewed', false);
        $example->andFieldGreaterThan('timeStamp', $afterStamp);

        return NOTIFICATIONS_BOL_NotificationDao::getInstance()->countByExample($example);
    }

    /**
     * Fetch notifications data
     *
     * @param int $lastFetchTime
     * @return array
     */
    public function getNotificationsData( $lastFetchTime )
    {
        $event = new BASE_CLASS_EventCollector(MOUSE_CLASS_EventHandler::EVENT_NOTIFICATION, ['lastFetchTime' => $lastFetchTime]);

        return OW::getEventManager()->trigger($event)->getData();
    }

    public function findUserById($id)
    {
        return $this->userDao->findById($id);
    }

    /**
     * Returns user profile data
     *
     * @param int $userId
     * @return array
     */
    public function getUserInfo( $userId )
    {
        $userService = BOL_UserService::getInstance();

        if( empty($userDto = $userService->findUserById( $userId ) ) )
        {
            return;
        }

		$user = array( 'id' => $userId );


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

        $user['isOnline'] = ($userService->findOnlineUserById($userId) && $showPresence);

        $avatarService = BOL_AvatarService::getInstance();

        if( $avatarService->userHasAvatar( $userId ) )
        {
            $user['avatar'] = [
                'small' => $avatarService->getAvatarUrl( $userId ),
                'big' => $avatarService->getAvatarUrl( $userId, 2 ),
                'original' => $avatarService->getAvatarUrl( $userId, 3 ),
            ];

            // used to check if a user has atleast a profile image
            $user['hasPicture'] = true;
        }
        else
        {
            $user['avatar'] = [
                'small' => $avatarService->getDefaultAvatarUrl( ),
                'big' => $this->noImageUrl($userId),
                'small' => $avatarService->getDefaultAvatarUrl( 3 ),
            ];

            $user['hasPicture'] = false;
        }

        //user role
        $roles = BOL_AuthorizationService::getInstance()->getRoleListOfUsers(array($userId));

        $user['role'] = !empty($roles[$userId]) ? $roles[$userId] : null;
        //display name
        $user['displayName'] = $userService->getDisplayName($userId);
        // $displayName = $userService->getDisplayName($userId);
        // $realNameArray = explode(' ', $displayName);
        // $user['displayName'] = count($realNameArray) > 1 ? $realNameArray[0].' '.array_pop($realNameArray)[0].'.' : $displayName;

        $locationField = OW::getPluginManager()->isPluginActive('googlelocation') ? 'googlemap_location' : MOUSE_CLASS_JoinForm::FIELD_LOCATION;

        //get question fields data
        $fieldData = $this->getFieldValue( $userDto->getId(), [
            'match_sex',
            'birthdate',
            'aboutme',
            $locationField
        ] );

        //Get user account type
		$accountType = $userDto->accountType;
		$accountType = OW::getLanguage()->text( "base", "questions_account_type_$accountType" );
		$user['sex'] = $accountType;

        //join date
        $user['joinDate'] = UTIL_DateTime::formatSimpleDate($userDto->joinStamp, true);

        //activity date
        $user['activityDate'] = UTIL_DateTime::formatSimpleDate($userDto->activityStamp, true);

        //match sex
        if( isset($fieldData['match_sex']) )
        {
            $user['match_sex'] = $this->getFieldValueLabel('match_sex', $fieldData['match_sex']);
        }

        $user['location'] = '';

        // Get user Google map location
        if( isset($fieldData[$locationField]) )
        {
            $location = $fieldData[$locationField];

            if( OW::getPluginManager()->isPluginActive('googlelocation') )
            {
                $user['location'] = $location['address'] ?? '';
            }
            else
            {
                $langKey = "questions_question_{$locationField}_value_{$location}";
    
                if( isset($location) && OW::getLanguage()->valueExist('base', $langKey) )
                {
                    $user['location'] = $this->getFieldValueLabel($locationField, $location);
                }
            }
        }

        //Get user age (Do some calculation and arrive at user's age)
        if( isset($fieldData['birthdate']) )
        {
            $birthdate = $fieldData['birthdate'];
            $birthdate = strtotime($birthdate);

            $birthYear = date('Y', $birthdate);
            $birthMonth = date('m', $birthdate);
            $birthDay = date('d', $birthdate);

            $user['age'] = UTIL_DateTime::getAge($birthYear, $birthMonth, $birthDay);
        }

        // about profile info
        if( isset($fieldData['aboutme']) )
        {
            $user['aboutme'] = $fieldData['aboutme'];
        }

        //user is email verified
		$user['verifications'] = array();

		if( (bool) $userDto->emailVerify == true )
		{
			$user['verifications']['email'] = array(
				'label' => OW::getLanguage()->text('apptheme', 'email_verified'),
				'class' => 'envelope',
				'status' => (bool) $userDto->emailVerify,
			);
		}

        //user approval
		if( ($isApproved = $userService->isApproved( $userDto->id ) == true ))
		{
			$user['verifications']['approved'] = array(
				'label' => OW::getLanguage()->text('apptheme', 'profile_approved'),
				'class' => 'check-circle',
				'status' => $isApproved,
			);
		}

        //hotlist status
        $user['isHot'] = false;

        if( OW::getPluginManager()->isPluginActive('hotlist') )
        {
            $user['isHot'] = !empty( HOTLIST_BOL_Service::getInstance()->findUserById($userDto->id));
        }

        //bookmark status
        $user['showBookmark'] = false;

        if( OW::getPluginManager()->isPluginActive('bookmarks') )
        {
            $user['showBookmark'] = true;
            $user['is_marked'] = $this->isUserMarked($userId);
        }

        //video info
        $user['videoInfo'] = false;

        if( OW::getPluginManager()->isPluginActive('video') )
        {
            $user['videoInfo'] = [
                'count' => VIDEO_BOL_ClipService::getInstance()->findUserClipsCount($userId),
                'url' => OW::getRouter()->urlForRoute('video_user_video_list', ['user' => $userDto->username]),
            ];
        }

        //photo info
        $user['photoInfo'] = false;

        if( OW::getPluginManager()->isPluginActive('photo') )
        {
            $user['photoInfo'] = [
                'count' => PHOTO_BOL_PhotoService::getInstance()->countUserPhotos($userId),
                'url' => OW::getRouter()->urlForRoute('photo.user_photos', ['user' => $userDto->username]),
            ];
        }

        //can video im
        $user['showVideo'] = false;

        if( OW::getPluginManager()->isPluginActive('videoim') )
        {
            $user['showVideo'] = true;
            $service = VIDEOIM_BOL_VideoImService::getInstance();

            list($isRequestSendAllowed, $errorMessage) =
                    $service->isAllowedSendVideoImRequest($userId, true);

            if ( !$isRequestSendAllowed )
            {
                $user['showVideo'] = false;
            }
        }

        //can video im
        if( OW::getPluginManager()->isPluginActive('matchmaking') && OW::getUser()->isAuthenticated() )
        {
            $service = MATCHMAKING_BOL_Service::getInstance();

            $user['compatibility'] = (int) $service->getCompatibility(OW::getUser()->getId(), $userId);
        }

        $event = OW::getEventManager()->trigger( new OW_Event( 'mouse.user_list_data', $user, $user ) );

        return $event->getData();
    }

    public function isUserMarked( $userId )
    {
        if( !OW::getPluginManager()->isPluginActive('bookmarks') )
        {
            return false;
        }

        $isMarked = BOOKMARKS_BOL_Service::getInstance()->isMarked(OW::getUser()->getId(), $userId);

        $isMarkedEvent = new OW_Event('bookmark.user.is_marked', ['isMarked' => $isMarked]);
        OW::getEventManager()->trigger($isMarkedEvent);

        return (bool) $isMarked;
    }

        /**
     * Returns profile question data
     *
     * @param int $userId
     * @param array|string $fields
     * @return array
     */
	public function getFieldValue( $userId, $fields )
	{
        $key = null;

        if( !is_array($fields) )
        {
            $fields = array($fields);

            $key = $fields;
        }

        $baseQuestion = BOL_QuestionService::getInstance()->getQuestionData(array( $userId ), $fields);

        if( empty($key) && isset($baseQuestion[$userId][$key]) )
        {
            return $baseQuestion[$userId][$key];
        }

		return $baseQuestion[$userId];
	}

    /**
     * Returns profile question data
     *
     * @param string $field
     * @param string $value
     * @return string
     */
	public function getFieldValueLabel($field, $value)
	{
		return BOL_QuestionService::getInstance()->getQuestionValueLang($field, $value);
	}

    public function listHasFiltered( )
    {
        $formData = OW::getSession()->get(USEARCH_CLASS_MainSearchForm::FORM_SESSEION_VAR);

        if( empty( $formData ) || !isset($formData[Form::ELEMENT_FORM_NAME]) )
        {
            return false;
        }

        return true;
    }

    public function getDataForUsersList( $listId, $orderType, $first, $count )
    {
        return array(
            $this->getSearchResultList($listId, $orderType, $first, $count),
            $this->getUserListCount( $listId )
        );
    }

    public function userListCountEvent( $listType )
    {
        $event = OW::getEventManager()->trigger(new OW_Event(
            MOUSE_CMP_UserList::EVENT_USERLIST_COUNT, [
            'listType' => $listType
        ]));

        return (int) $event->getData();
    }

    public function getUserListCount($listId)
    {
        return BOL_SearchService::getInstance()->countSearchResultItem($listId);
    }

    public function getSearchResultList( $listId, $listType, $from, $count, $includeList = array(), $excludeList = array() )
    {
        $usearchService = USEARCH_BOL_Service::getInstance();

        $userIdList = null;
        $excludeList = $excludeList;

        if ( empty($excludeList) )
        {
            $excludeList = array();
        }

        if ( OW::getUser()->isAuthenticated() )
        {
            $excludeList[] = OW::getUser()->getId();
        }

        // if( !$this->listHasFiltered() )
        // {
        //     $userIdList = $this->userDao->findUserIdsList($includeList, $excludeList);
        // }
        // else
        // {
        //     $userIdList = $usearchService->getUserIdList($listId, 0, BOL_SearchService::USER_LIST_SIZE, $excludeList);
        // }
        $userIdList = $usearchService->getUserIdList($listId, 0, BOL_SearchService::USER_LIST_SIZE, $excludeList);

        if ( empty($userIdList) )
        {
            return array();
        }

        switch($listType)
        {
            case USEARCH_BOL_Service::LIST_ORDER_NEW:

                return $this->searchDao->findSearchResultListOrderedByRecentlyJoined( $userIdList, $from, $count );

                break;

            case USEARCH_BOL_Service::LIST_ORDER_MATCH_COMPATIBILITY:

                if ( OW::getPluginManager()->isPluginActive('matchmaking') && OW::getUser()->isAuthenticated() )
                {
                    $users = BOL_UserService::getInstance()->findUserListByIdList($userIdList);

                    $list = array();

                    foreach ( $users as $user )
                    {
                        $list[$user->id] = $user;
                    }

                    $result = MATCHMAKING_BOL_Service::getInstance()->findCompatibilityByUserIdList( OW::getUser()->getId(), $userIdList, $from, $count);
                    $usersList = array();

                    foreach ( $result as $item )
                    {
                        $usersList[$item['userId']] = $list[$item['userId']];
                    }

                    return $usersList;
                }

                break;

            case USEARCH_BOL_Service::LIST_ORDER_DISTANCE:

                if ( OW::getPluginManager()->isPluginActive('googlelocation') && OW::getUser()->isAuthenticated() )
                {


                    $result = BOL_QuestionService::getInstance()->getQuestionData(array(OW::getUser()->getId()), array('googlemap_location'));

                    if ( !empty($result[OW::getUser()->getId()]['googlemap_location']['json']) )
                    {
                        $location = $result[OW::getUser()->getId()]['googlemap_location'];

                        return GOOGLELOCATION_BOL_LocationService::getInstance()->getListOrderedByDistance( $userIdList, $from, $count, $location['latitude'], $location['longitude'] );
                    }
                }


            default:
                $params = array(
                    'idList' => $userIdList,
                    'orderType' => $listType,
                    'from' => $from,
                    'count' => $count,
                    'userId' => OW::getUser()->isAuthenticated() ? OW::getUser()->getId() : 0
                );

                $event = new OW_Event('usearch.get_ordered_list', $params, array());
                OW::getEventManager()->trigger($event);

                $data = $event->getData();

                if ( !empty($data) && is_array($data) )
                {
                    return $data;
                }
        }

        return $this->searchDao->findSearchResultListByLatestActivity( $userIdList, $from, $count );
    }

    public function userMatchesBySex( $userId )
	{
		// $qdata = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array('sex'));
		$qdata = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array('sex', 'match_sex'));

		return USEARCH_BOL_Service::getInstance()->findUserIdListByQuestionValues($qdata[$userId], 0, BOL_SearchService::USER_LIST_SIZE);
	}

	public function findUserMatches( $userId )
	{
        $listId = 0;

		if ( count($userIdList = (array) $this->userMatchesBySex( $userId )) > 0 )
		{
			$listId = BOL_SearchService::getInstance()->saveSearchResult( $userIdList );
		}

		OW::getSession()->set(BOL_SearchService::SEARCH_RESULT_ID_VARIABLE, $listId);
		OW::getSession()->set(self::SESSION_MATCH_USER_ID, $userId);

        return $this->userMatchesBySex($userId);

    }

    // show female/male no image if no avatar
    public function noImageUrl($userId)
    {
        $pluginImgUrl =  OW::getPluginManager()->getPlugin('mouse')->getStaticUrl() .'images';
        $maleNoImage = $pluginImgUrl . '/male-no-image.png';
        $femaleNoImage = $pluginImgUrl . '/female-no-image.png';
        $noImage = $maleNoImage;
        $gender = BOL_QuestionService::getInstance()->getQuestionData([$userId], ['sex']);

        if(isset($gender[$userId]['sex']) && $gender[$userId]['sex'] == 8)
        {
            $noImage = $maleNoImage;
        }
        elseif( isset($gender[$userId]['sex']) && $gender[$userId]['sex'] == 4)
        {
            $noImage = $femaleNoImage;
        }

        return $noImage;
    }

}