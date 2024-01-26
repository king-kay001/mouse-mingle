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

class MOUSE_CTRL_Admin extends ADMIN_CTRL_Theme
{
    /**
     * @var BOL_ThemeService
     *
     */
    private $themeService;
    /**
     * @var BASE_CMP_ContentMenu
     */
    protected $menu;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->themeService = BOL_ThemeService::getInstance();
    }

    public function plugin()
    {
        $form = new Form('admin-theme-plugin-settings');

        $profileQuestionList = BOL_QuestionService::getInstance()->findAllQuestions();
        $profileQuestionFields = [];

        $componentSettings = new BASE_CMP_ComponentSettings('admin-page');
        $iconList = [];

        foreach( IconCollection::allWithLabel() as $icon )
        {
            $iconList[$icon['class']] = $icon['label'];
        }

        foreach( $profileQuestionList as $question )
        {
            $fieldName = "question-icon-{$question->name}";

            $field = new Selectbox( $fieldName );
            $field->setLabel(OW::getLanguage()->text('base', "questions_question_{$question->name}_label"));
            $field->setHasInvitation(true);
            $field->setOptions($iconList);
            $field->setValue(MOUSE_BOL_Service::getInstance()->getProfileQuestionIcon($question->name));
            $field->addAttribute('onchange', UTIL_JsGenerator::composeJsString('(function(self) {
                let icon = $("<span>").addClass($(self).val());
                $("#"+{$questionSelector}).find(".ow_desc").html(icon);
            })(this)', [
                'questionSelector' => "{$fieldName}-cont"
            ]));

            $profileQuestionFields[$question->name] = $fieldName;
            $form->addElement($field);
        }

        $submit = new Submit('save');
        $form->addElement($submit);

        $this->addForm($form);

        if( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $values = $form->getValues();
            $questionIconValues = [];

            foreach( $values as $name => $value )
            {
                if( in_array($name, $profileQuestionFields) && !empty($value) )
                {
                    $questionIconValues[array_search ($name, $profileQuestionFields)] = $value;
                }
            }

            if( OW::getConfig()->configExists('mekirim', MOUSE_BOL_Service::QUESTIONS_ICON_CONFIG) )
            {
                OW::getConfig()->saveConfig('mekirim', MOUSE_BOL_Service::QUESTIONS_ICON_CONFIG, serialize($questionIconValues));
            }
            else
            {
                OW::getConfig()->addConfig('mekirim', MOUSE_BOL_Service::QUESTIONS_ICON_CONFIG, serialize($questionIconValues));
            }

            $this->redirect();
        }

        $this->assign('profileQuestionList', $profileQuestionFields);
    }

    public function settings()
    {
        $dto = $this->themeService->findThemeByKey(OW::getConfig()->getValue('base', 'selectedTheme'));

        if ( $dto === null )
        {
            throw new LogicException("Can't find theme `" . OW::getConfig()->getValue('base', 'selectedTheme') . "`");
        }

        $assignArray = (array) json_decode($dto->getDescription());

        $assignArray['iconUrl'] = $this->themeService->getStaticUrl($dto->getKey()) . BOL_ThemeService::ICON_FILE;
        $assignArray['name'] = $dto->getKey();
        $assignArray['title'] = $dto->getTitle();
        $this->assign('themeInfo', $assignArray);
        $this->assign('resetUrl', OW::getRouter()->urlFor(__CLASS__, 'reset'));

        $controls = $this->themeService->findThemeControls($dto->getId());

        if ( empty($controls) )
        {
            $this->assign('noControls', true);
        }
        else
        {
            $form = new ThemeEditForm($controls);

            $this->assign('inputArray', $form->getFormElements());

            $this->addForm($form);

            if ( OW::getRequest()->isPost() )
            {
                if ( $form->isValid($_POST) )
                {
                    $this->themeService->saveThemeControls($dto->getId(), $form->getValues());
                    $this->themeService->updateCustomCssFile($dto->getId());
                    $this->redirect();
                }
            }
        }

        $this->menu->getElement('settings')->setActive(true);
    }

    public function init()
    {
        $router = OW_Router::getInstance();

        $pageActions = array(
            array('name' => 'settings', 'iconClass' => 'ow_ic_gear_wheel'),
            array('name' => 'plugin', 'iconClass' => 'ow_ic_plugin'),
            array('name' => 'css', 'iconClass' => 'ow_ic_files'),
            array('name' => 'graphics', 'iconClass' => 'ow_ic_picture')
        );

        $menuItems = array();

        foreach ( $pageActions as $key => $item )
        {
            $menuItem = new BASE_MenuItem();
            $menuItem->setKey($item['name']);
            $menuItem->setOrder($key);
            $menuItem->setIconClass($item['iconClass']);

            if( $item['name'] === 'plugin')
            {
                $menuItem->setLabel(OW::getLanguage()->text('mekirim', 'admin_theme_plugin_menu_item'));
                $menuItem->setUrl($router->urlFor(__CLASS__, 'plugin'));
            }
            else
            {
                $menuItem->setLabel(OW::getLanguage()->text('admin', 'sidebar_menu_item_' . $item['name']));
                $menuItem->setUrl($router->urlForRoute('admin_theme_' . $item['name']));
            }

            $menuItems[] = $menuItem;
        }

        $this->menu = new BASE_CMP_ContentMenu($menuItems);

        $this->addComponent('contentMenu', $this->menu);

        OW::getNavigation()->activateMenuItem(OW_Navigation::ADMIN_APPEARANCE, 'admin', 'sidebar_menu_item_theme_edit');
        $this->setPageHeading(OW::getLanguage()->text('admin', 'themes_settings_page_title'));
    }
}