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

class MOUSE_BOL_NotificationDao extends NOTIFICATIONS_BOL_NotificationDao
{
    /**
     * @var MOUSE_BOL_NotificationDao
     */
    private static $classInstance;

    /**
     * @return MOUSE_BOL_NotificationDao
     */
    public static function getInstance()
    {
        if (self::$classInstance === null){
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function getNewNotificationList($userId, $afterStamp, $offset, $limit)
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('viewed', false);
        
        if ( $afterStamp )
        {
            $example->andFieldGreaterThan('timeStamp', $afterStamp);
        }
        $example->setOrder('timeStamp DESC');
        $example->setLimitClause($offset, $limit);

        return $this->findListByExample($example);
    }

    public function getNotificationList($userId, $beforeStamp, $offset, $limit)
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);
        $example->andFieldLessOrEqual('timeStamp', $beforeStamp);

        $example->setLimitClause($offset, $limit);
        $example->setOrder('viewed, timeStamp DESC');

        return $this->findListByExample($example);
    }
}