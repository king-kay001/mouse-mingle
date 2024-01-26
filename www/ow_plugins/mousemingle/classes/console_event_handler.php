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

class MOUSE_CLASS_ConsoleEventHandler
{
    /**
     * Class instance
     *
     * @var MOUSE_CLASS_ConsoleEventHandler
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return MOUSE_CLASS_ConsoleEventHandler
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function init()
    {
        // remove base console items
        OW::getEventManager()->unbind('console.collect_items', [(BASE_CLASS_ConsoleEventHandler::getInstance()), 'collectItems']);

        // add mekirim console items
        OW::getEventManager()->bind('console.collect_items', [$this, 'collectItems']);

        // remove mailbox console items
        if( OW::getPluginManager()->isPluginActive('mailbox') )
        {
            OW::getEventManager()->unbind('console.collect_items', [(new MAILBOX_CLASS_EventHandler), 'onCollectConsoleItems']);
        }

        // remove notifications console item
        if( OW::getPluginManager()->isPluginActive('notifications') )
        {
            OW::getEventManager()->unbind('console.collect_items', [(NOTIFICATIONS_CLASS_ConsoleBridge::getInstance()), 'collectItems']);
        }

        // remove fake mailbox item
        if( OW::getPluginManager()->isPluginActive('fake') )
        {
            OW::getEventManager()->unbind('console.collect_items', [(FAKE_CLASS_EventHandler::getInstance()), 'collectConsoleItemFakeMessages']);
        }
    }

    public function collectItems( BASE_CLASS_ConsoleItemCollector $event )
    {
        $language = OW::getLanguage();
        $router = OW::getRouter();

        if ( OW::getUser()->isAuthenticated() )
        {
            // Admin menu
            if ( OW::getUser()->isAdmin() )
            {
                $item = new BASE_CMP_ConsoleDropdownMenu($language->text('admin', 'main_menu_admin'));
                $item->setUrl($router->urlForRoute('admin_default'));
                $item->addItem('head', array('label' => $language->text('admin', 'console_item_admin_dashboard'), 'url' => $router->urlForRoute('admin_default')));
                $item->addItem('main', array('label' => $language->text('admin', 'console_item_manage_theme'), 'url' => $router->urlForRoute('admin_themes_edit')));
                $item->addItem('main', array('label' => $language->text('admin', 'console_item_manage_users'), 'url' => $router->urlForRoute('admin_users_browse')));
                $item->addItem('main', array('label' => $language->text('admin', 'console_item_manage_pages'), 'url' => $router->urlForRoute('admin_pages_main')));
                $item->addItem('main', array('label' => $language->text('admin', 'console_item_manage_plugins'), 'url' => $router->urlForRoute('admin_plugins_installed')));

                $event->addItem($item, 1);
            }

            /**
             * My Profile Menu
             *
             * @var $item BASE_CMP_MyProfileConsoleItem
             */
            // $item = OW::getClassInstance("BASE_CMP_MyProfileConsoleItem");
            // $event->addItem($item, 2);

            // add mailbox console items
            if( OW::getPluginManager()->isPluginActive('mailbox') )
            {
                $item = OW::getClassInstance('MAILBOX_CMP_ConsoleMailbox');
                $event->addItem("<div class='ow_hidden' style='display:none !important;'>{$item->render()}</div>", 4);
            }

            // add upgrade console link
            if( OW::getPluginManager()->isPluginActive('membership') )
            {
                $item = new BASE_CMP_ConsoleButton($language->text('mekirim', 'console_upgrade_premium_label'), OW::getRouter()->urlForRoute('membership_subscribe'));
                $item->addClass('ow_console_membership_btn');

                $event->addItem($item, 10);
            }

            $item = OW::getClassInstance('BASE_CMP_ConsoleSwitchLanguage');
            $event->addItem($item, 0);
        }
        else
        {
            $item = new BASE_CMP_ConsoleButton($language->text('base', 'sign_in_submit_label'), OW::getRouter()->urlForRoute('static_sign_in'));
            $event->addItem($item, 1);
        }
    }
}