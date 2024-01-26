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

 class MOUSE_CLASS_NotificationData
 {
    /**
     * @var string
     */
    private $label;
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $prefix;
    /**
     * @var string
     */
    private $key;
    /**
     * @var int
     */
    private $countNew;
    /**
     * @var int
     */
    private $countAll;
    /**
     * @var string
     */
    private $iconClass;
    /**
     * @var boolean
     */
    private $active = false;

    /**
     * Constructor.
     *
     * @param array $params
     */
    public function __construct( $name )
    {
        $this->setKey( $name );
    }

    /**
     * @param string $iconClass
     * @return MOUSE_CLASS_NotificationData
     */
    public function setIconClass( $iconClass )
    {
        $this->iconClass = $iconClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return $this->iconClass;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return int
     */
    public function getNewCount()
    {
        return $this->countNew;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $key
     * @return MOUSE_CLASS_NotificationData
     */
    public function setKey( $key )
    {
        $this->key = trim($key);
        return $this;
    }

    /**
     * @param string $label
     * @return MOUSE_CLASS_NotificationData
     */
    public function setLabel( $label )
    {
        $this->label = trim($label);
        return $this;
    }

    /**
     * @param int $counter
     * @return MOUSE_CLASS_NotificationData
     */
    public function setNewCounter( $counter )
    {
        $this->countNew = (int) $counter;
        return $this;
    }

    /**
     * @param string $url
     * @return MOUSE_CLASS_NotificationData
     */
    public function setUrl( $url )
    {
        $this->url = trim($url);
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCountAll()
    {
        return $this->countAll;
    }

    /**
     * @param boolean $newWindow
     * @return MOUSE_CLASS_NotificationData
     */
    public function setCountAll( $counter )
    {
        $this->countAll = $counter;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string  $prefix
     * @return MOUSE_CLASS_NotificationData
     */
    public function setPrefix( $prefix )
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function isActive()
    {
        return $this->active;
    }

    public function setActive( $active )
    {
        $this->active = (bool) $active;
        return $this;
    }

    public function getValues()
    {
        return [
            'key' => $this->getKey(),
            'label' => $this->getLabel(),
            'countAll' => $this->getCountAll(),
            'countNew' => $this->getNewCount(),
            'url' => $this->getUrl(),
        ];
    }
 }