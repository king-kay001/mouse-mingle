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

class MOUSE_CMP_SwitchLanguage extends BASE_CMP_SwitchLanguage
{
    public function __construct()
    {
        $languages = BOL_LanguageService::getInstance()->getLanguages();
        $session_language_id = BOL_LanguageService::getInstance()->getCurrent()->getId();

        $active_languages = array();

        foreach($languages as $id=>$language)
        {
            if ( $language->status == 'active' )
            {
                $tag = $this->parseCountryFromTag($language->tag);

                $active_lang = array(
                    'id'=>$language->id,
                    'label'=>$tag['label'],
                    'order'=>$language->order,
                    'tag'=>$language->tag,
                    'class'=>"ow_console_lang{$tag['country']}",
                    'url'=> OW::getRequest()->buildUrlQueryString(null, array( "language_id"=>$language->id ) ),
                    'is_current'=>false
                    );

                if ( $session_language_id == $language->id )
                {
                        $active_lang['is_current'] = true;
                        $this->assign('label', $tag['label']);
                        $this->assign('class', "ow_console_lang{$tag['country']}");
                }

                $active_languages[] = $active_lang;
            }
        }

        if ( count($active_languages) <= 1)
        {
            $this->setVisible(false);
            return;
        }

        function sortActiveLanguages($lang1, $lang2 )
        {
            return ( $lang1['order'] < $lang2['order'] ) ? -1 : 1;
        }
        usort($active_languages, 'sortActiveLanguages');

        parent::__construct($active_languages);
    }

    protected function parseCountryFromTag($tag)
    {
        $tags = preg_match("/^([a-zA-Z]{2})$|^([a-zA-Z]{2})-([a-zA-Z]{2})(-\w*)?$/", $tag, $matches);

        if (empty($matches))
        {
            return array("label"=>$tag, "country"=>"");
        }
        if (!empty($matches[1]))
        {
            $country = strtolower($matches[1]);
            return array("label"=>$matches[1], "country"=>"_".$country);
        }
        else if (!empty($matches[2]))
        {
            $country = strtolower($matches[3]);
            return array("label"=>$matches[2], "country"=>"_".$country);
        }

        return "";
    }
}