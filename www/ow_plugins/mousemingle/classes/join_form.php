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

 class MOUSE_CLASS_JoinForm extends BASE_CLASS_UserQuestionForm
 {
    const FIELD_LOCATION = 'field_c62da0e9dbeac0babb69a75f1327c908';

    const SESSION_JOIN_DATA = 'joinData';
    const SESSION_JOIN_STEP = 'joinStep';

    const SESSION_REAL_QUESTION_LIST = 'join.real_question_list';
    const SESSION_ALL_QUESTION_LIST = 'join.all_question_list';
    const SESSION_START_STAMP = 'join.session_start_stamp';

    protected $stepCount = 1;
    protected $isLastStep = false;
    protected $questionValuesList = array();
    protected $data = array();
    protected $questions = array();
    protected $questionNameList = array();

    /**
     * @var BOL_QuestionService
     */
    public $questionService;

    public function __construct( $name )
    {
        parent::__construct( $name );

        $lang = OW::getLanguage();

        $this->questionService = BOL_QuestionService::getInstance();

        $this->getQuestions();
        $this->setQuestionValues();

        $this->addQuestions(
            $this->questions,
            $this->questionValuesList,
            $this->updateJoinData()
        );

        $this->setQuestionsLabel();
        $this->addClassToBaseQuestions();

        $submitBtn = new Submit('register');
        $submitBtn->setValue($lang->text('mekirim', 'register_now'));
        $this->addElement($submitBtn);

        $this->initJs();
    }

    public function setQuestionValues()
    {
        $namesToUnset = [];

        foreach ( $this->questions as $key => $question )
        {
            if(isset($question['realName']))
            {
                $namesToUnset[$question['realName']] = $question['name'];
                $this->questionNameList[] = $question['realName'];
            }
            else
            {
                $this->questionNameList[] = $question['name'];
            }
        }

        $this->questionValuesList = BOL_QuestionService::getInstance()->findQuestionsValuesByQuestionNameList($this->questionNameList);

        foreach( $namesToUnset as $realName => $name )
        {
            if( isset($this->questionValuesList[$realName] ) )
            {
                $this->questionNameList[] = $name;
                $this->questionValuesList[$name] = $this->questionValuesList[$realName];

                unset($this->questionNameList[array_search($realName, $this->questionNameList)]);
                unset($this->questionValuesList[$realName]);
            }
        }
    }

    protected function addFieldValidator( $formField, $question )
    {
        if ( $question['name'] === 'email' )
        {
            $formField->addValidator(new BASE_CLASS_JoinEmailValidator());
        }

        if ( $question['name'] === 'username' )
        {
            $formField->addValidator(new BASE_CLASS_JoinUsernameValidator());
        }
    }

    public function addClassToBaseQuestions()
    {
        foreach ( $this->questions as $question )
        {
            if ( $question['name'] == 'username' )
            {
                $this->getElement($question['name'])->addAttribute("class", "ow_username_validator");
            }

            if ( $question['name'] == 'email' )
            {
                $this->getElement($question['name'])->addAttribute("class", "ow_email_validator");
            }
        }
    }

    public function setQuestionsLabel()
    {
        foreach ( $this->questions as $question )
        {
            $event = OW::getEventManager()->trigger(new OW_Event('base.questions_field_add_label_join', $question, true));
            $data = $event->getData();

            $label = !empty($data['label']) ? $data['label'] : OW::getLanguage()->text('base', 'questions_question_' . $question['name'] . '_label');

            $this->getElement($question['name'])->setLabel($label);

            switch( $question['presentation'])
            {
                case BOL_QuestionService::QUESTION_PRESENTATION_TEXT :
                case BOL_QuestionService::QUESTION_PRESENTATION_TEXTAREA :
                case BOL_QuestionService::QUESTION_PRESENTATION_SELECT :
                case BOL_QuestionService::QUESTION_PRESENTATION_PASSWORD :
                    $this->getElement($question['name'])->setHasInvitation(true);
                    $this->getElement($question['name'])->setInvitation($label);
                break;
            }
        }
    }

    public function getQuestions()
    {
        $this->questions = $this->questionService->findBaseSignUpQuestions();

        $questionDtoList = $this->getDtoQuestionList([$this->getLocationFieldName()]);

        if ( !empty($questionDtoList['sex']) )
        {
            $sex = get_object_vars($questionDtoList['sex']);
            array_push($this->questions, $sex);
        }
        
        if ( !empty($questionDtoList['match_sex']) )
        {
            $matchsex = get_object_vars($questionDtoList['match_sex']);
            array_push($this->questions, $matchsex);
        }

        // pv($this->questions, 1);

    }

    public function getDtoQuestionList( $additionalQuestions = [] )
    {
        $questionNameList = array_merge(array('sex', 'match_sex'), $additionalQuestions);

        return $this->questionService->findQuestionByNameList($questionNameList);
    }

    public function initJs()
    {
        $lang = OW::getLanguage();
        
        $lang->addKeyForJs('base', 'join_error_username_not_valid');
        $lang->addKeyForJs('base', 'join_error_username_already_exist');
        $lang->addKeyForJs('base', 'join_error_email_not_valid');
        $lang->addKeyForJs('base', 'join_error_email_already_exist');
        $lang->addKeyForJs('base', 'join_error_password_not_valid');
        $lang->addKeyForJs('base', 'join_error_password_too_short');
        $lang->addKeyForJs('base', 'join_error_password_too_long');

        //include js
        OW::getDocument()->addOnloadScript(UTIL_JsGenerator::composeJsString('window.join = new OW_BaseFieldValidators({$params}, '.UTIL_Validator::EMAIL_PATTERN.', '.UTIL_Validator::USER_NAME_PATTERN.');', [
            'params' => [
                'formName' => $this->getName(),
                'responderUrl' => OW::getRouter()->urlFor("BASE_CTRL_Join", "ajaxResponder"),
                'passwordMaxLength' => UTIL_Validator::PASSWORD_MAX_LENGTH,
                'passwordMinLength' => UTIL_Validator::PASSWORD_MIN_LENGTH
            ],
        ]));

        $jsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "base_field_validators.js");
    }

    public function processForm()
    {
        if( !$this->isValid($_POST) )
        {
            OW::getFeedback()->error(OW::getLanguage()->text('base', 'join_join_error'));
            return;
        }

        $data = $this->getValues();

        $event = new OW_Event(OW_EventManager::ON_BEFORE_USER_REGISTER, $data);
        OW::getEventManager()->trigger($event);

        $userService = BOL_UserService::getInstance();

        OW::getSession()->set(self::SESSION_JOIN_DATA, $data);
    
        // check if user exits
        if($userService->findByEmail($data['email']))
        {
            OW::getFeedback()->error(OW::getLanguage()->text('mekirim', 'user_already_exists'));
            return;
        }

        // create user profile
        $user = $userService->createUser($data['username'], $data['password'], $data['email']);

        // save user data
        if ( !empty($user->id) )
        {
            $userData = [
                'sex' => $data['sex'],
                'birthdate' => $data['birthdate'],
            ];

            if( isset($data['location']) )
            {
                // $userData['googlemap_location'] = $data['googlemap_location'];
                $userData[$this->getLocationFieldName()] = $data['location'];
            }

            BOL_QuestionService::getInstance()->saveQuestionsData($userData, $user->id);

            OW::getUser()->login($user->id);

            // trigger on user register event
            OW::getEventManager()->trigger( new OW_Event(OW_EventManager::ON_USER_REGISTER,
                array(
                    'userId' => $user->id,
                    'method' => 'native',
                    'params' => []
                )
            ));

            // show success feedback
            OW::getFeedback()->info(OW::getLanguage()->text('base', 'join_successful_join'));

            // send confirmation email if any
            if ( OW::getConfig()->getValue('base', 'confirm_email') )
            {
                BOL_EmailVerifyService::getInstance()->sendUserVerificationMail($user);
            }

            // Redirect the user after successful login
            OW::getApplication()->redirect(OW::getRouter()->getBaseUrl());
        }
        else
        {
            OW::getFeedback()->error(OW::getLanguage()->text('base', 'join_join_error'));
        }
    }

    protected function updateJoinData()
    {
        $joinData = OW::getSession()->get(self::SESSION_JOIN_DATA);

        if ( empty($joinData) )
        {
            return;
        }

        $this->data = $joinData;

        $list = OW::getSession()->get(self::SESSION_REAL_QUESTION_LIST);

        if ( !empty($list) )
        {
            foreach ( $list as $fakeName => $realName )
            {
                if ( !empty($joinData[$realName]) )
                {
                    unset($this->data[$realName]);
                    $this->data[$fakeName] = $joinData[$realName];
                }
            }
        }

        return $this->data;
    }

    public function getLocationFieldName()
    {
        return OW::getPluginManager()->isPluginActive('googlelocation') ? 'googlemap_location' : self::FIELD_LOCATION;
    }
 }