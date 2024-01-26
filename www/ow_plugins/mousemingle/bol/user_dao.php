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

class MOUSE_BOL_UserDao extends BOL_UserDao
{
    public static $classInstance;

    public static function getInstance()
    {
        if(self::$classInstance === null){
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getAll($offset=0, $limit=20)
    {
        $query = "
			SELECT id, username
			FROM `{$this->getTableName()}`
			ORDER BY RAND() LIMIT $offset, $limit
		";

		return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), [$offset, $limit]);
    }

    /**
     * Find user ids list
     *
     * @return array $excludeList
     */
    public function findUserIdsList($includeList = array(), $excludeList = array() )
    {
        $example = new OW_Example();
        // $example->andFieldNotEqual('id', OW::getUser()->getId());

        if( $excludeList )
        {
            $example->andFieldNotInArray('id', $excludeList);
        }

        if(!empty($includeList))
        {
            $example->andFieldInArray('id', $includeList);
        }

        return $this->findIdListByExample($example);
    }
}