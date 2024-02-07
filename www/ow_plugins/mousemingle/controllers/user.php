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

class MOUSE_CTRL_User extends BASE_CTRL_User
{
    private $masterPageTpl;

    public function __construct()
    {
        parent::__construct();

        $this->masterPageTpl = 'guest';
    }

    public function index()
    {
        $this->masterPageTpl = 'index';

        $regForm = new MOUSE_CLASS_LandingRegForm();
        $this->addForm($regForm);

        if(OW::getRequest()->isPost())
        {
            $regForm->processForm();
        }
    }

    public function signUp()
    { 
        $signupForm = new MOUSE_CLASS_SignupForm();
        $this->addForm($signupForm);


        if(OW::getRequest()->isPost())
        {
            $signupForm->processForm();
        }
    }

    public function signIn()
    {
        parent::standardSignIn();
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        OW::getDocument()
            ->getMasterPage()
            ->setTemplate(OW::getThemeManager()->getMasterPageTemplate($this->masterPageTpl));
    }

    public function forgotPassword()
    {
        parent::forgotPassword();
        $this->setTemplate(OW::getPluginManager()->getPlugin('mouse')->getCtrlViewDir(). 'user_forgot_password.html');
    }
}