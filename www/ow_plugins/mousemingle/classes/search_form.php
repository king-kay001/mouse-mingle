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

class MOUSE_CLASS_SearchForm extends BASE_CLASS_UserQuestionForm
{
    const FIELD_LOCATION = MOUSE_CLASS_JoinForm::FIELD_LOCATION;

    const FORM_SESSEION_VAR = USEARCH_CLASS_QuickSearchForm::FORM_SESSEION_VAR;

    /**
     * @var BOL_QuestionService
     */
    public $questionService;
    /**
     * @var USEARCH_BOL_Service
     */
    public $searchService;

    public function __construct()
    {
        parent::__construct('search_form');

        $this->questionService = BOL_QuestionService::getInstance();
        $this->searchService = USEARCH_BOL_Service::getInstance();

        $lang = OW::getLanguage();
        $sessionData = OW::getSession()->get(self::FORM_SESSEION_VAR);

        $questionNameList = $this->searchService->getQuickSerchQuestionNames();
        array_push($questionNameList, self::FIELD_LOCATION);

        $questionValueList = $this->questionService->findQuestionsValuesByQuestionNameList($questionNameList);
        
        if ( $sessionData === null )
        {
            $sessionData = array();

            if ( OW::getUser()->isAuthenticated() )
            {
                $userId = OW::getUser()->getId();
                $data = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array('sex', 'match_sex', self::FIELD_LOCATION));
                
                if ( !empty($data[$userId]['sex']) )
                {
                    $sessionData['sex'] = $data[$userId]['sex'];
                }
                
                if ( !empty($data[$userId]['match_sex']) )
                {
                    for ( $i = 0; $i < BOL_QuestionService::MAX_QUESTION_VALUES_COUNT; $i++ )
                    {
                        if( pow(2, $i) & $data[$userId]['match_sex'] )
                        {
                            $sessionData['match_sex'] = pow(2, $i);
                            break;
                        }
                    }
                }

                if( $this->googleLocationIsActive() )
                {
                    $sessionData['googlemap_location']['distance'] = 50;
                }
                else
                {
                    if ( !empty($data[$userId][self::FIELD_LOCATION]) )
                    {
                        $sessionData['googlemap_location'] = $data[$userId][self::FIELD_LOCATION];
                    }
                }
                
                OW::getSession()->set(self::FORM_SESSEION_VAR, $sessionData);
            }
        }

        if ( !empty($sessionData['match_sex']) )
        {
            if ( is_array($sessionData['match_sex']) )
            {
                $sessionData['match_sex'] = array_shift($sessionData['match_sex']);
            }
            else
            {
                for ( $i = 0; $i < BOL_QuestionService::MAX_QUESTION_VALUES_COUNT; $i++ )
                {
                    if( pow(2, $i) & $sessionData['match_sex'] )
                    {
                        $sessionData['match_sex'] = pow(2, $i);
                        break;
                    }
                }
            }
        }

        // prepare questions
        $questionDtoList = BOL_QuestionService::getInstance()->findQuestionByNameList($questionNameList);

        $questions = array();
        $questionList = array();
        $orderedQuestionList = array();

        /* @var $question BOL_Question */
        foreach ( $questionDtoList as $key => $question )
        {
            $dataList = (array) $question;
            $questions[$question->name] = $dataList;

            $isRequired = in_array($question->name, array('match_sex')) ? 1 : 0;
            $questions[$question->name]['required'] = $isRequired;

            if ( $question->name == 'sex' || $question->name == 'match_sex' )
            {
                unset($questions[$question->name]);
            }
            else
            {
                $questionList[$question->name] = $dataList;
            }
        }

        foreach ( $questionNameList as $questionName )
        {
            if ( !empty($questionDtoList[$questionName]) )
            {
                $orderedQuestionList[] = $questionDtoList[$questionName];
            }
        }

        $this->addQuestions($questions, $questionValueList, $sessionData);

        if( $this->googleLocationIsActive() )
        {
            /**
             * @var GOOGLELOCATION_CLASS_LocationSearch
             */
            $locationField = $this->getElement('googlemap_location');
            
            if ( $locationField && method_exists( $locationField, 'setDistance') )
            {
                $value = $locationField->getValue();

                if ( empty($value['distance']) )
                {
                    $locationField->setDistance(50);
                }
            }
            
        }
        else
        {
            $locationField = new Selectbox('googlemap_location');
            $locationField->setInvitation($lang->text('mekirim', 'search_location'));

            if( !empty($sessionData['googlemap_location']) )
            {
                $locationField->setValue((int) $sessionData['googlemap_location']);
            }
            
            $this->setFieldOptions($locationField, self::FIELD_LOCATION, $questionValueList[self::FIELD_LOCATION] ?? []);

            $this->addElement($locationField);
        };

        // match sex
        $matchSex = new Selectbox('match_sex');
        $this->setFieldOptions($matchSex, 'match_sex', $questionValueList['sex']);
        $matchSex->setInvitation($lang->text('mekirim', 'search_match_sex'));

        if ( !empty($sessionData['match_sex']) && !is_array($sessionData['match_sex']) )
        {
            $matchSex->setValue($sessionData['match_sex']);
        }
        
        $this->addElement($matchSex);

        // age field
        if( !isset( $questions['birthdate'] ) )
        {
            $birthdate = new USEARCH_CLASS_AgeRangeField('age_range', null);
            $birthdate->setLabel($lang->text('mekirim', 'search_age_range'));
    
            $configs = !empty($params['configs']) ? BOL_QuestionService::getInstance()->getQuestionConfig($params['configs'], 'year_range') : null;
            
            $max = !empty($configs['from']) ? date("Y") - (int) $configs['from'] : null;
            $min = !empty($configs['to']) ? date("Y") - (int) $configs['to'] : null;
    
            $birthdate->setMaxAge($max);
            $birthdate->setMinAge($min);
    
            $this->addElement($birthdate);
        }

        $submitBtn = new Submit('filter');
        $submitBtn->setValue($lang->text('mekirim', 'search'));
        $this->addElement($submitBtn);
    }

    protected function setFieldOptions( $formField, $questionName, array $questionValues )
    {
        parent::setFieldOptions($formField, $questionName, $questionValues);

        if ( $questionName == 'match_sex' )
        {
            $options = array_reverse($formField->getOptions(), true);
            $formField->setOptions($options);
        }

        $formField->setLabel(OW::getLanguage()->text('base', 'questions_question_' . $questionName . '_label'));
    }

    public function process()
    {
        if( !OW::getRequest()->isPost() || !$this->isValid($_POST))
        {
            return;
        }
        $data = $this->getValues();

        if ( !OW::getUser()->isAuthorized('base', 'search_users') )
        {
            if (OW::getPluginManager()->isPluginActive('creditsshortage')) {
                CREDITSSHORTAGE_BOL_Service::getInstance()->redirectToBuyCredits();
            } else {
                $status = BOL_AuthorizationService::getInstance()->getActionStatus('base', 'search_users');

                throw new AuthorizationException($status['msg']);
            }
        }

        OW::getSession()->set(self::FORM_SESSEION_VAR, $data);

        if( !$this->googleLocationIsActive() && isset($data['googlemap_location']) )
        {
            $data[self::FIELD_LOCATION] = $data['googlemap_location'];
            unset($data['googlemap_location']);
        }

        $addParams = array('join' => '', 'where' => '');

        $data = USEARCH_BOL_Service::getInstance()->updateSearchData( $data );
        $data = USEARCH_BOL_Service::getInstance()->updateQuickSearchData( $data );

        $userIdList = USEARCH_BOL_Service::getInstance()->findUserIdListByQuestionValues(
            $data, 0, BOL_SearchService::USER_LIST_SIZE, false, $addParams
        );
        
        $listId = BOL_SearchService::getInstance()->saveSearchResult($userIdList);

        OW::getSession()->set(BOL_SearchService::SEARCH_RESULT_ID_VARIABLE, $listId);
        OW::getSession()->set('usearch_search_data', $data);

        BOL_AuthorizationService::getInstance()->trackAction('base', 'search_users');

        OW::getApplication()->redirect();
    }

    protected function getPresentationClass( $presentation, $questionName, $configs = null )
    {
        $event = new OW_Event('base.questions_field_get_label', array(
            'presentation' => $presentation,
            'fieldName' => $questionName,
            'configs' => $configs,
            'type' => 'edit'
        ));

        OW::getEventManager()->trigger($event);

        $label = $event->getData();

        $class = null;

        $event = new OW_Event('base.questions_field_init', array(
            'type' => 'search',
            'presentation' => $presentation,
            'fieldName' => $questionName,
            'configs' => $configs
        ));

        OW::getEventManager()->trigger($event);

        $class = $event->getData();

        if ( empty($class) )
        {
            switch ( $presentation )
            {
                case BOL_QuestionService::QUESTION_PRESENTATION_TEXT :
                case BOL_QuestionService::QUESTION_PRESENTATION_TEXTAREA :
                    $class = new TextField($questionName, self::PLUGIN_KEY);
                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX :
                    $class = new CheckboxField($questionName, self::PLUGIN_KEY);
                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_RADIO :
                case BOL_QuestionService::QUESTION_PRESENTATION_SELECT :
                case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX :
                    $class = new Selectbox($questionName, self::PLUGIN_KEY);
                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE :
                case BOL_QuestionService::QUESTION_PRESENTATION_AGE :

                    $class = new USEARCH_CLASS_AgeRangeField($questionName);
                    
                    if ( !empty($configs) && mb_strlen( trim($configs) ) > 0 )
                    {
                        $configsList = json_decode($configs, true);
                        foreach ( $configsList as $name => $value )
                        {
                            if ( $name = 'year_range' && isset($value['from']) && isset($value['to']) )
                            {
                                $class->setMinYear($value['from']);
                                $class->setMaxYear($value['to']);
                            }
                        }
                    }

                    $class->addValidator(new USEARCH_CLASS_AgeRangeValidator($class->getMinAge(), $class->getMaxAge()));
                    
                    break;
                    
                case BOL_QuestionService::QUESTION_PRESENTATION_RANGE :
                    $class = new Range($questionName, USEARCH_CLASS_QuickSearchForm::PLUGIN_KEY);

                    if ( empty($this->birthdayConfig) )
                    {
                        $birthday = $this->findQuestionByName("birthdate");
                        if ( !empty($birthday) )
                        {
                            $this->birthdayConfig = ($birthday->custom);
                        }
                    }
                    
                    $rangeValidator = new RangeValidator();
                    
                    if ( !empty($this->birthdayConfig) && mb_strlen( trim($this->birthdayConfig) ) > 0 )
                    {
                        $configsList = json_decode($this->birthdayConfig, true);
                        foreach ( $configsList as $name => $value )
                        {
                            if ( $name = 'year_range' && isset($value['from']) && isset($value['to']) )
                            {
                                $class->setMinValue(date("Y") - $value['to']);
                                $class->setMaxValue(date("Y") - $value['from']);
                                
                                $rangeValidator->setMinValue(date("Y") - $value['to']);
                                $rangeValidator->setMaxValue(date("Y") - $value['from']);
                            }
                        }
                    }

                    $class->addValidator($rangeValidator);
                    
                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_DATE :
                    $class = new DateRange($questionName, USEARCH_CLASS_QuickSearchForm::PLUGIN_KEY);

                    if ( !empty($configs) && mb_strlen( trim($configs) ) > 0 )
                    {
                        $configsList = json_decode($configs, true);
                        foreach ( $configsList as $name => $value )
                        {
                            if ( $name = 'year_range' && isset($value['from']) && isset($value['to']) )
                            {
                                $class->setMinYear($value['from']);
                                $class->setMaxYear($value['to']);
                            }
                        }
                    }

                    $class->addValidator(new DateValidator($class->getMinYear(), $class->getMaxYear()));
                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_URL :
                    $class = new TextField($questionName, USEARCH_CLASS_QuickSearchForm::PLUGIN_KEY);
                    $class->addValidator(new UrlValidator());
                    break;
            }

            if ( !empty($label) )
            {
                $class->setLabel($label);
            }

            if ( empty($class) )
            {
                $class = BOL_QuestionService::getInstance()->getSearchPresentationClass($presentation, $questionName, $configs);
            }
        }

        if( $questionName === 'googlemap_location' && $this->googleLocationIsActive() )
        {
            $class = new MOUSE_CLASS_LocationSearch($questionName);
            // $location->setInvitation($lang->text('googlelocation', 'googlemap_location_search_invitation'));
        }

        return $class;
    }

    protected function googleLocationIsActive()
    {
        return OW::getPluginManager()->isPluginActive('googlelocation');
    }
}