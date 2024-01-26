"use strict";
OW.ajaxUserListLoader = function (listType, page, itemCount, limit) {
    const cont = "#search-user-list-loader";
    let nextPage = 0;

    OW.loadComponent("MOUSE_CMP_UserList", [listType, page, limit], cont);

    $(window).scroll(function() {
        var diff = $(document).height() - ($(window).scrollTop() + $(window).height());

        if ( diff < 100 )
        {
            // Get next page offset
            nextPage = ++page;

            const offset = (nextPage - 1) * limit;

            // Stop if no more items to fetch
            if( itemCount < offset )
            {
                return;
            }

            OW.loadComponent("MOUSE_CMP_UserList", [listType, nextPage, limit], function(html) {
                $(cont).find('.ow_user_list').append($(html).html());
            });
        }
    });
}

// Display mailbox/FB style chat image using floatbox

const findAndShowChatImage = (e) => {
    let pictureItem = e.target.closest('.ow_dialog_picture_item');
    if(pictureItem)
    {
        e.preventDefault();
        let chatImage = pictureItem.querySelector('div > div > p > a > img');
        if(chatImage)
        {
            OW.showImageInFloatBox(chatImage.src);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    let floatChatContainer = document.querySelector('#dialogsContainer');
    if (floatChatContainer) {
        floatChatContainer.addEventListener('click', findAndShowChatImage);
    }

    let mailboxLog = document.querySelector('#conversationLog');
    if (mailboxLog) {
        mailboxLog.addEventListener('click', findAndShowChatImage);
    }
});

function OW_Mouse( params )
{
    $.extend(this, {
        lastFetchTime: null,
        notificationsData: null,
        notifyUrlList: null,
        consoleItems: [],
        gift: function(userId, userName) {
            return OW.ajaxFloatBox(
                'MOUSE_CMP_SendGift',
                { recipientId: userId },
                {
                    width : 580,
                    title: OW.getLanguageText('virtualgifts', 'send_gift_to', {user: userName})
                }
            );
        },

        setNotificationsData: function( data )
        {
            this.notificationsData = data;
            this.lastFetchTime = Date.now();
        },

        updateConsoleItems: function (prevCounter = 0 ) {
            const cls = OW.Console;

            for (const key in cls.items) {
                if (cls.items.hasOwnProperty(key) && this.notifyUrlList[key]) {
                    // get console item
                    const item = cls.items[key], itemData = item.data;
                    
                    // Use an observer to listen for data changes in the console item
                    if( itemData ) {
                        itemData.set('counter.previous', prevCounter);
                        itemData.prevCount = prevCounter;
                        this.consoleItems[key] = itemData;
                    }
                }
            }
        },

        checkConsoleUpdate: function() {
            const cls = Object.assign(this, OW.Console);
            this.consoleItems = []

            updateConsoleItems();
            console.log(this.consoleItems);
        
            // Check if new notification is available
            for (const key in this.consoleItems) {
                // get console item
                const item = this.consoleItems[key];
                // Use an observer to listen for data changes in the console item
                this.consoleItems[key].addObserver(this.consoleItems[key]);

                this.consoleItems[key].onDataChange = function (data) {
                    const counter = data.get('counter');
                    const newCounter = counter.new;
                    const previousCounter = counter.previous || 0;

                    // Check if newCounter has increased
                    if (newCounter > previousCounter) {
                        // Create a language key for notification counter
                        let msg = OW.getLanguageText('mouse', key + '_notification_counter', {
                            counter: newCounter,
                            url: model.notifyUrlList[key]
                        });
    
                        // Display a notification message
                        OW.info(msg);
                    }
                }
            }
        },        

        showNotification: function ( )
        {
            if( !this.notificationsData )
            {
                return;
            }

            $.each(this.notificationsData, function(k, data) {
                if( data.countNew > 0 ) {
                    let msg = OW.getLanguageText('mouse', data.key+'_notification_counter', {
                        counter:data.countNew,
                        url:data.url
                    });

                    OW.info(msg);
                }

                // show counter on notification tabs
                let navKey = data.key == 'notifications' ? 'default' : data.key;
                $('.ow_mekirim_notification_tabs').find('.item_'+navKey+'_count').text(data.countAll);
            });
        },
    }, params);

    const model = this;

    $(document).ready(function () {
        // model.checkConsoleUpdate();
    });

    // bind gift button click
    OW.bind('mouse.gift_click', function(userId, userName){
        model.sendGift(userId, userName);
    });

    // bind edit question field
    OW.bind('mouse.edit_user_question', function(fieldName) {
        const questionBox = "#user-edit-box-" + fieldName;
        const inputBox = "#user-edit-box-" + fieldName + "-content";
        const submitBtn = "#user-edit-box-" + fieldName + "-submit";

        const floatbox = new OW_FloatBox({
            $title: $(questionBox).find(".ow_user_question_name label").text(),
            $contents: $(inputBox),
            width: '550px'
        });

        const getLabel = function(v) {
            return 'label[for="input_editForm_' + fieldName + '_' + v + '"]';
        };

        floatbox.bind('close', function() {
            const input = $(inputBox).find(".ow_user_question_input_field");
            const display = $(questionBox).find('.ow_user_question_value');
            const presentation = $(questionBox).data('presentation');

            switch (presentation) {
                case 'text':
                case 'textarea':
                case 'email':
                    display.text(input.val());
                    break;

                case 'age':
                    display.text($(inputBox).find('input[name="' + fieldName + '"]').val());
                    break;

                case 'radio':
                    const value = $(inputBox).find('input[name="' + fieldName + '"]:checked').val();
                    display.text($(inputBox).find(getLabel(value)).text());
                    break;

                case 'select':
                case 'fselect':
                    display.text(input.find('option[value="' + input.val() + '"]').text());
                    break;

                case 'multicheckbox':
                    const selectedCheckboxes = $(inputBox).find('input[type="checkbox"]:checked');
                    const checklist = selectedCheckboxes.map(function() {
                        return $(inputBox).find(getLabel($(this).val())).text();
                    }).get();
                    display.text(checklist.join(', '));
                    break;
            }
        });

        $(submitBtn).click(function() {
            floatbox.close({
                sender: "button",
                button: this
            });
        });
    });

    //Adding command into the ping queue
    OW.getPing().addCommand('mouse.ping_command', {
        params: {},
        before: function()
        {
            // before ping goes here
            this.params.lastFetchTime = model.lastFetchTime;
        },
        after: function( data )
        {
            model.setNotificationsData(data);
            model.showNotification();
        }
    }).start(model.pingInterval); // Time interval in milliseconds


    // Create floatbox for user photo album
    OW.bind('mouse.user_album_photos', function(albumId, showFooter=true) {
        const self = $.extend(this, {
            contentBox: "#ow-album-photos-" + albumId,
            closeBtn:"#ow-floatbox-close-" + albumId,
        });

        // Load album photos via ajax
        OW.loadComponent("MOUSE_CMP_UserPhotoAlbumPhotos", [albumId, showFooter], $(self.contentBox));

        self.floatbox = new OW_FloatBox({
            $title: '',
            $contents: $(self.contentBox),
            width: '550px'
        });

        $(self.closeBtn).click(function() {
            self.floatbox.close();
        });
    });

    // bind bookmark button click
    OW.bind('mouse.bookmark_click', function(userId) {
        const elem = $(this);

        BOOKMARKS.markState(userId, function( data, textStatus, jqXHR ) {
            if ( data.mark === true ) {
                OW.info(OW.getLanguageText('mouse', 'marked_notify_message'));
                $(elem).addClass('active');
            } else {
                OW.info(OW.getLanguageText('mouse', 'unmarked_notify_message'));
                $(elem).removeClass('active');
            }
        });
    });
}

OW.Mouse = null;

OW.NueSignUp = function(params) {
    Object.assign(this, params);

    this.form = owForms[this.formName];
    this.questionsArray = Object.values(this.questions);
    this.activeElement = null;
    this.completed = [];

    const form = $("#" + this.id);
    const controls = form.find('.ow_nuesignup_control');
    const btnLogin = controls.find('.btn-nuesignup-login');
    const btnNext = controls.find('.btn-nuesignup-next');
    const btnSubmit = $('.btn-nuesignup-submit', controls);

    this.init = function() {
        this.activeElement = this.getFirstQuestion();

        let cont = form.hide().find('.ow_nuesignup_questions');
        $('.input-wrap', cont).hide();
        form.show();

        this.showNextQuestion();
        cont.removeClass('ow_hidden');

        btnNext.click(() => this.submitQuestion());
    }

    this.getFirstQuestion = function() {
        return this.questionsArray.length > 0 ? this.questionsArray[0] : null;
    };

    this.getLastQuestion = function() {
        return this.questionsArray.length > 0 ? this.questionsArray[this.questionsArray.length - 1] : null;
    };

    this.getNextQuestion = function(currentQuestion) {
        const index = this.questionsArray.indexOf(currentQuestion);
        return index !== -1 && index < this.questionsArray.length - 1
            ? this.questionsArray[index + 1]
            : null;
    };

    this.submitQuestion = function() {
        const element = this.getActiveElement();

        if (!element) {
            return;
        }

        element.removeErrors();

        try {
            element.validate();
        } catch (e) {
            this.handleValidationError(element, e);
            return;
        }

        this.completed.push(this.activeElement);
        form.find('.input-wrap.input-field-' + element.name).hide();

        // Find next question
        const nextQuestion = this.getNextQuestion(this.activeElement);
        if (nextQuestion) {
            this.activeElement = nextQuestion;
            this.showNextQuestion();
        }
    };

    this.handleValidationError = function(element, error) {
        element.input.focus();
        OW.error(this.validateErrorMessage ? this.validateErrorMessage : error);
    };

    this.showNextQuestion = function() {
        const nextElem = this.getActiveElement();

        if (nextElem) {
            form.find('.input-wrap.input-field-' + nextElem.name).show();
            nextElem.input.focus();
        }

        this.updateButtonVisibility();
    };

    this.updateButtonVisibility = function() {
        if (this.isFirst()) {
            btnNext.show();
            btnLogin.show();
            btnSubmit.hide();
        } else if (this.isLast()) {
            btnNext.hide();
            btnLogin.hide();
            btnSubmit.show();
        } else {
            btnNext.show();
            btnLogin.hide();
            btnSubmit.hide();
        }
    };

    this.getActiveElement = function() {
        const activeQuestion = this.getActiveQuestion();

        if (!activeQuestion || typeof this.form.elements[activeQuestion] === 'undefined') {
            return;
        }

        return this.form.elements[activeQuestion];
    };

    this.getActiveQuestion = function() {
        return this.activeElement;
    };

    this.isLast = function() {
        return this.activeElement === this.getLastQuestion();
    };

    this.isFirst = function() {
        return this.activeElement === this.getFirstQuestion();
    };

    this.init();
};

window.addEventListener("DOMContentLoaded", function(){
    if (window.location.pathname.endsWith('/mailbox') || window.location.pathname.endsWith('/messages'))  {
        // Hide the browser scroll bar.
        document.body.style.overflow = 'hidden';

        // hide chat dialog
        let chatCont = document.querySelector('.ow_chat_cont');
        if(chatCont)
        {
            chatCont.style.display = 'none';
        }

    }
});

