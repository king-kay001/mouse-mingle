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

class MOUSE_CTRL_Notifications extends OW_ActionController
{
    public function index( $params )
    {
        if( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        
        $this->addComponent('menu', $this->contentMenu());

        $type = isset($params['type']) ? $params['type'] : 'default';

        $pluginTypeList = [
            'notifications' => 'default',
            'ocsguests' => 'views',
            'bookmarks' => 'likes',
            'matchmaking' => 'matches',
            'bookmarks' => 'bookmarks',
        ];

        foreach( $pluginTypeList as $plugin => $listType )
        {
            if( !OW::getPluginManager()->isPluginActive($plugin) )
            {
                unset($pluginTypeList[$plugin]);
            }
        }

        if( isset($pluginTypeList['bookmarks']) )
        {
            $pluginTypeList['bookmarks_likes'] = 'likes';
        }

        if( !in_array($type, $pluginTypeList) )
        {
            throw new Redirect404Exception();
        }
        
        $userId = OW::getUser()->getId();

        switch( $type )
        {
            case 'default':
                $this->addComponent('cmp', new MOUSE_CMP_NotificationList($userId) );
            break;
            case 'views':
            case 'matches':
            case 'likes':
            case 'bookmarks':
                $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
                $perPage = (int) OW::getConfig()->getValue('base', OW::getPluginManager()->isPluginActive('skadate') ? 'users_on_page' : 'users_count_on_page');

                $jsString = UTIL_JsGenerator::composeJsString('OW.ajaxUserListLoader({$listType}, {$currentPage}, {$itemCount}, {$limit});', [
                    'listType' => $type,
                    'currentPage' => $page,
                    'limit' => $perPage,
                    'itemCount' => MOUSE_BOL_UserService::getInstance()->userListCountEvent( $type ),
                ]);

                // initialize OW.ajaxUserListLoader js
                OW::getDocument()->addOnloadScript($jsString);

                $this->assign('cmp', UTIL_HtmlTag::generateTag('div', ['id' => 'search-user-list-loader'], true));
            break;

            default:
                throw new Redirect404Exception();
        }
        
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'mekirim', 'notification');

        $this->setTemplate(OW::getPluginManager()->getPlugin('mouse')->getCtrlViewDir() . 'notifications_index.html');
    }

    protected function contentMenu()
    {
        $menuItems = [
            'default' => [
                'url' => OW::getRouter()->urlForRoute('mouse.notification'),
                'iconClass' => 'ow_ic_bell',
                'itemCount' => null
            ]
        ];

        if( OW::getPluginManager()->isPluginActive('ocsguests') )
        {
            $menuItems['views'] = [
                'iconClass' => 'ow_ic_eye',
                'itemCount' => null
            ];
        }

        if( OW::getPluginManager()->isPluginActive('bookmarks') )
        {
            $menuItems['likes'] = [
                'iconClass' => 'ow_ic_heart',
                'itemCount' => null
            ];
            $menuItems['bookmarks'] = [
                'iconClass' => 'ow_ic_bookmark',
                'itemCount' => null
            ];
        }

        if( OW::getPluginManager()->isPluginActive('matchmaking') )
        {
            $menuItems['matches'] = [
                'iconClass' => 'ow_ic_match',
                'itemCount' => null
            ];
        }

        $menus = [];

        foreach( $menuItems as $key => $data )
        {
            $menuItem = new BASE_MenuItem();
            $menuItem->setKey($key);
            $menuItem->setLabel(OW::getLanguage()->text('mekirim', "notifications_{$key}"));
            $menuItem->setUrl( isset($data['url']) ? $data['url'] : OW::getRouter()->urlForRoute('mouse.notification_listing', [
                'type' => $key
            ]));
            $menuItem->setIconClass($data['iconClass']);
            $menuItem->itemCount = $data['itemCount'];

            $menus[] = $menuItem;
        }

        return new MOUSE_CMP_NotificationTabs($menus);
    }
}