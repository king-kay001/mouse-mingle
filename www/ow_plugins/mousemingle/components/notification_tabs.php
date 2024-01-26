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

class MOUSE_CMP_NotificationTabs extends BASE_CMP_ContentMenu
{
    public function __construct( $menuItems = null )
    {
        parent::__construct($menuItems);

        $cmpId = uniqid('notification-');
        $this->assign('cmpId', $cmpId);
        
        $this->setTemplate(OW::getPluginManager()->getPlugin('mouse')->getCmpViewDir() . 'notification_tabs.html');
    }

    protected function getItemViewData( BASE_MenuItem $menuItem )
    {
        return array(
            'label' => $menuItem->getLabel(),
            'url' => $menuItem->getUrl(),
            'class' => $menuItem->getPrefix() . '_' . $menuItem->getKey(),
            'iconClass' => $menuItem->getIconClass(),
            'itemCount' => $menuItem->itemCount,
            'active' => $menuItem->isActive(),
            'new_window' => $menuItem->getNewWindow(),
            'prefix' => $menuItem->getPrefix(),
            'key' => $menuItem->getKey()
        );
    }
}