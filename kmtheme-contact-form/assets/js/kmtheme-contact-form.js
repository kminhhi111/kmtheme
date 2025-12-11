/**
 * KMTheme Contact Form JavaScript
 */

(function($) {
    'use strict';
    
    function initContactForm() {
        $('#kmtheme-contact-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('.form-submit-btn');
            var $btnText = $submitBtn.find('.btn-text');
            var $btnLoading = $submitBtn.find('.btn-loading');
            var $message = $form.find('.form-message');
            
            // Get form data
            var formData = {
                action: 'kmtheme_submit_contact_form',
                nonce: kmthemeCF.nonce,
                name: $('#cf-name').val(),
                email: $('#cf-email').val(),
                telefon: $('#cf-telefon').val(),
                nachricht: $('#cf-nachricht').val(),
            };
            
            // Add reCAPTCHA response if exists
            var recaptchaResponse = $form.find('[name="g-recaptcha-response"]').val();
            if (recaptchaResponse) {
                formData['g-recaptcha-response'] = recaptchaResponse;
            }
            
            // Disable submit button and show loading
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            $message.hide().removeClass('success error');
            
            // Submit form
            $.ajax({
                url: kmthemeCF.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Success
                        $message
                            .addClass('success')
                            .text(response.data.message)
                            .fadeIn();
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Reset reCAPTCHA if exists
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                    } else {
                        // Error
                        $message
                            .addClass('error')
                            .text(response.data.message || 'Ein Fehler ist aufgetreten.')
                            .fadeIn();
                    }
                },
                error: function() {
                    $message
                        .addClass('error')
                        .text('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.')
                        .fadeIn();
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            });
        });
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initContactForm();
    });
    
    // Re-initialize for AJAX loaded content (Flatsome PJAX)
    $(document).on('flatsome-pjax-loaded', function() {
        initContactForm();
    });
    
})(jQuery);

