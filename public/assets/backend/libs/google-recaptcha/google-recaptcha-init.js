"use strict";

let recaptchaSiteKey = '';
let recaptchaGenerateStatus = false;

function showDefaultCaptchaSection($element, defaultSectionElement) {
    $(defaultSectionElement).hide();
}

function generateRecaptcha($element, $input, action, defaultSectionElement) {
    let $form = $element.closest('form');
    $form.find('[type="submit"]').removeAttr('disabled');
}

function getSessionRecaptchaCode(sessionKey, inputSelector) {
    // Do nothing
}

$(document).ready(function() {
    $('.default-captcha-container').hide();
    $('.dynamic-default-and-recaptcha-section').hide();
});
