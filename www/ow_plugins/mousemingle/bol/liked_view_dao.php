<?php

class MOUSE_BOL_LikedViewDao extends OW_BaseDao
{
    /**
     * Singleton instance.
     *
     * @var MOUSE_BOL_LikedViewDao
     */
    private static $classInstance;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns an instance of class.
     *
     * @return MOUSE_BOL_LikedViewDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     */
    public function getDtoClassName()
    {
        return 'MOUSE_BOL_LikedView';
    }
    
    /**
     * @see OW_BaseDao::getTableName()
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'mekirim_liked_view';
    }

    public function likeIsMarked($userId, $markerId)
    {
        $example = new OW_Example();

        $example->andFieldEqual("userId", $userId);
        $example->andFieldEqual("markerId", $markerId);
        
        return (bool) $this->findListByExample($example);
    }
    
    public function findViewedLikeIdList( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual("userId", $userId);
        
        $viewedList = $this->findListByExample($example);
        $markerIdList = [];

        foreach( $viewedList as $view )
        {
            $markerIdList[] = $view->markerId;
        }

        return $markerIdList;
    }
}
