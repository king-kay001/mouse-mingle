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

class MOUSE_CTRL_Usearch extends USEARCH_CTRL_Search
{
    const USERS_PER_PAGE = 20;

    public function index()
    {
        if( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        // activate main menu item
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'usearch', 'menu_item_search');

        // add search form
        $form = new MOUSE_CLASS_SearchForm();
        $form->process();

        $this->addForm($form);

        // get number of users per page
        $perPage = OW::getEventManager()->trigger(new OW_Event('usearch.get_search_result_limit', array( ), self::USERS_PER_PAGE))->getData();

        // get page number
        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;

        // compose js string
        $jsString = UTIL_JsGenerator::composeJsString('OW.ajaxUserListLoader("search", {$currentPage}, {$itemCount}, {$limit});', [
            'listType' => 'search',
            'currentPage' => $page,
            'itemCount' => MOUSE_BOL_UserService::getInstance()->userListCountEvent( 'search' ),
            'limit' => $perPage,
        ]);

        // initialize OW.ajaxUserListLoader js
        OW::getDocument()->addOnloadScript($jsString);

        $this->assign('isFiltered', MOUSE_BOL_UserService::getInstance()->listHasFiltered());
        
        // overwrite page template
        $this->setTemplate(OW::getPluginManager()->getPlugin('mouse')->getCtrlViewDir() . 'usearch_index.html');
    }

    public function clearSearch()
    {
        OW::getSession()->delete(MOUSE_CLASS_SearchForm::FORM_SESSEION_VAR);
        OW::getSession()->delete(BOL_SearchService::SEARCH_RESULT_ID_VARIABLE);
        OW::getSession()->delete('usearch_search_data');

        MOUSE_BOL_UserService::getInstance()->findUserMatches(OW::getUser()->getId());

        $this->redirect(OW::getRouter()->urlForRoute('users-search'));
    }

    public function form()
    {
       return $this->index();
    }
}