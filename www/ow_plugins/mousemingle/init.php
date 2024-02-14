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

//initialise plugin variable
$plugin = OW::getPluginManager()->getPlugin('mouse');
// initialize ow router
$router = OW::getRouter();

//  Add landing page route
$router->addRoute(new OW_Route('guest.landing', 'landing', 'MOUSE_CTRL_User', 'index'));
// Reroute join page
$router->removeRoute('base_join')
    ->addRoute(new OW_Route('base_join', 'sign-up', 'MOUSE_CTRL_User', 'signUp'));

//Reroute sign in page
$router->removeRoute('static_sign_in')
    ->addRoute(new OW_Route('static_sign_in', 'sign-in', 'MOUSE_CTRL_User', 'signIn'));

// Reroute profile pages
$router->removeRoute('base_user_profile')
    ->addRoute(new OW_Route('base_user_profile', 'user/:username', 'MOUSE_CTRL_Profile', 'index'));

$router->removeRoute('base_member_profile')
    ->addRoute(new OW_Route('base_member_profile', 'my-profile', 'MOUSE_CTRL_Profile', 'myProfile'));

$router->removeRoute('base_edit')
    ->addRoute(new OW_Route('base_edit', 'profile/edit', 'MOUSE_CTRL_ProfileEdit', 'index'));
$router->removeRoute('base_edit_user_datails')
    ->addRoute(new OW_Route('base_edit_user_datails', 'profile/:userId/edit/', 'MOUSE_CTRL_ProfileEdit', 'index'));

// Default photos route
OW::getRouter()->addRoute(new OW_Route('mouse.view_photo_list', 'photos', 'PHOTO_CTRL_Photo', 'viewList', array('listType' => array('default' => 'latest'))));

// add profile base route for breadcrumb
$router->addRoute(new OW_Route('mekirim_member_profile', 'profile', 'MOUSE_CTRL_Profile', 'myProfile'));
// profile -> settings 
$router->addRoute(new OW_Route('profile_settings', 'profile/settings', 'MOUSE_CTRL_Profile', 'settings'));
// profile -> settings -> language
$router->addRoute(new OW_Route('profile-language', 'profile/settings/language', 'MOUSE_CTRL_Profile', 'language'));
// profile -> settings -> preference
$router->removeRoute('base_preference_index')->addRoute(new OW_Route('base_preference_index', 'profile/settings/preference', 'MOUSE_CTRL_Preference', 'index'));
// profile -> settings -> matches
$router->removeRoute('matchmaking_preferences')->addRoute(new OW_Route('matchmaking_preferences', 'profile/settings/matches', 'MOUSE_CTRL_Matches', 'preferences'));
// profile -> settings -> email-notifications
$router->removeRoute('notifications-settings')->addRoute(new OW_Route('notifications-settings', 'profile/settings/email-notifications', 'MOUSE_CTRL_Email', 'settings'));

// Reroute subscribe page
$router->removeRoute('membership_subscribe')->addRoute(
    new OW_Route('membership_subscribe', 'upgrade', 'MOUSE_CTRL_Subscribe', 'upgrade')
);

// Search routes
$router->addRoute(new OW_Route('mekirim-usearch-clear', 'users/refresh-search', 'MOUSE_CTRL_Usearch', 'clearSearch'));

//Reroute stripe billing order form
$router->removeRoute('billingstripe.order_form')->addRoute(
    new OW_Route('billingstripe.order_form', 'stripe/order', 'MOUSE_CTRL_Stripe', 'orderForm')
);

// Reroute user credit index
$router->removeRoute('usercredits.buy_credits')->addRoute(
    new OW_Route('usercredits.buy_credits', 'user-credits/buy-credits', 'MOUSE_CTRL_BuyCredit', 'index')
);

// Reroute contact page
$router->removeRoute('contactus.index')->addRoute(
    new OW_Route('contactus.index', 'contact', "MOUSE_CTRL_Contact", 'index')
);

// route mailbox index
$router->addRoute(
    new OW_Route('mouse.mailbox_default', 'messages', 'MAILBOX_CTRL_Messages', 'index')
);

// Reroute userphotos page
$router->removeRoute('photo.user_photos')->addRoute(
    new OW_Route('photo.user_photos', 'photo/userphotos/:user/', 'MOUSE_CTRL_Photo', 'userPhotos')
);
$router->removeRoute('view_photo_list')->addRoute(
    new OW_Route('view_photo_list', 'photo/viewlist/:listType/', 'MOUSE_CTRL_Photo', 'viewList', 
    array('listType' => array('default' => 'latest')))
);
 
// Add notifications page route
$router->addRoute(new OW_Route('mouse.notification', 'my-notifications', 'MOUSE_CTRL_Notifications', 'index'));
$router->addRoute(new OW_Route('mouse.notification_listing', 'my-notifications/:type', 'MOUSE_CTRL_Notifications', 'index'));

// Initialize plugin event handler
MOUSE_CLASS_EventHandler::getInstance()->init();

$baseDecoratorsToRegister = ['ucard'];

foreach ( $baseDecoratorsToRegister as $name )
{
    OW::getThemeManager()->addDecorator($name, 'mouse');
}

require_once( $plugin->getRootDir() . 'smarty/function.php' );

// import lang (for dev only)
// BOL_LanguageService::getInstance()->importPrefixFromDir($plugin->getRootDir() . 'langs', true, true, true);

// BOL_LanguageService::getInstance()->importPrefixFromDir($plugin->getRootDir() . 'defaultlangs', true, true, true);

// MOUSE_BOL_Service::getInstance()->mybookersListCount(OW::getUser()->getId());