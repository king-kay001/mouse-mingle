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

 class MOUSE_CLASS_LanguageForm extends Form
{
    protected $langService;

    public function __construct()
    {
        parent::__construct('language_form');

        $this->langService = BOL_LanguageService::getInstance();

        $languages = $this->langService->getLanguages();

        $languageFieldOptions = [];
        $currentLanguage = $this->langService->getCurrent()->getId();

        foreach ($languages as $language)
        {
            if($language->status == 'active')
            {
                $languageFieldOptions[$language->id] = $language->label;
            }
        }

        $languageField = new RadioField('language');
        $languageField->setOptions($languageFieldOptions);
        $languageField->setValue($currentLanguage);
        $this->addElement($languageField);

        $submitBtn = new Submit('save');
        $submitBtn->setValue('Save');
        $this->addElement($submitBtn);

    }

    public function processForm()
    {
        if(OW::getRequest()->isPost() && $this->isValid($_POST))
        {
            $data = $this->getValues();

            // $language = $this->langService->findById($data['language']);

            // update current session language
            // $this->langService->setCurrentLanguage($language);

            OW::getFeedback()->info(OW::getLanguage()->text('mekirim', 'language_changed'));

            // update current session language and refresh page
            OW::getApplication()->redirect(OW::getRequest()->buildUrlQueryString(OW::getRouter()->uriForRoute('profile-language'), array( "language_id"=>$data['language'] ) ));
        }
    }
}