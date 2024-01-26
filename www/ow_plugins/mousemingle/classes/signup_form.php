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

class MOUSE_CLASS_SignupForm extends MOUSE_CLASS_JoinForm
{
    public function __construct()
    {
        parent::__construct('signupForm');

        $lang = OW::getLanguage();

        $submitBtn = new Submit('register');
        $submitBtn->setValue($lang->text('mekirim', 'complete'));
        $this->addElement($submitBtn);
    }

    public function getQuestions()
    {
        parent::getQuestions();

        $questionDtoList = $this->getDtoQuestionList(array( $this->getLocationFieldName()));
        
        if ( !empty($questionDtoList[$this->getLocationFieldName()]) )
        {
            $location = get_object_vars($questionDtoList[$this->getLocationFieldName()]);
            $location['realName'] = $location['name'];
            $location['name'] = 'location';

            array_push($this->questions, $location);
        }
        else
        {
            
        }

        foreach( $this->questions as $key => $question )
        {
            if( $question['name'] === 'sex' )
            {
                $this->questions[$key]['presentation'] = BOL_QuestionService::QUESTION_PRESENTATION_RADIO;
            }
        }

        // pv($this->questions, 1);
    }

    public function initJs()
    {
        parent::initJs();

        OW::getDocument()->addOnloadScript(UTIL_JsGenerator::composeJsString('OW.NueSignUp({$params})', [
            'params' => [
                'formName' => $this->getName(),
                'id' => $this->getId(),
                'questions' => $this->questionNameList,
            ]
        ]));
    }
}