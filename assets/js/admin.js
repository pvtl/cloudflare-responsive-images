/**
 * Admin JavaScript for Cloudflare Responsive Images plugin
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Toggle dependent fields
    function toggleDependentFields() {
        var enableTransform = $('#enable_transform').is(':checked');
        
        // Show/hide transform settings based on enable_transform
        $('.cfri-transform-settings').toggle(enableTransform);
    }
    
    // Bind change events
    $('#enable_transform').on('change', toggleDependentFields);
    
    // Initial call
    toggleDependentFields();
    
    // Add visual feedback for form validation
    $('input[required], select[required]').on('blur', function() {
        if ($(this).val() === '') {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });
    
    // Add error styling
    $('<style>')
        .prop('type', 'text/css')
        .html('.error { border-color: #d63638 !important; box-shadow: 0 0 0 1px #d63638 !important; }')
        .appendTo('head');
});
