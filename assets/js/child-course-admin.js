/**
 * Child Course Admin JavaScript
 *
 * Handles showing/hiding parent course selector based on child course checkbox
 * Uses WooCommerce enhanced select (select2)
 *
 * @package EB_Course_Experience
 */

(function ($) {
    'use strict';

    /**
     * Initialize child course admin functionality
     */
    function init() {
        // Initialize select2 for existing parent course selects
        initEnhancedSelect();

        // Handle checkbox change for simple products
        $(document).on('change', '.ce-is-child-course', function () {
            var $checkbox = $(this);
            var productId = $checkbox.data('product-id');
            var postType = $checkbox.data('post-type');
            var index = $checkbox.data('index');
            var isChecked = $checkbox.is(':checked');

            // Show/hide parent course selector
            toggleParentCourseSelector(productId, postType, index, isChecked);
        });

        // Handle variations - when variation is loaded
        $(document).on('woocommerce_variations_loaded', function () {
            $('.ce-is-child-course').each(function () {
                var $checkbox = $(this);
                var productId = $checkbox.data('product-id');
                var postType = $checkbox.data('post-type');
                var index = $checkbox.data('index');
                var isChecked = $checkbox.is(':checked');

                toggleParentCourseSelector(productId, postType, index, isChecked);
            });

            // Initialize select2 for variations
            initEnhancedSelect();
        });

        // Handle variation row expansion
        $(document).on('click', '.woocommerce_variation', function () {
            var $row = $(this);
            setTimeout(function () {
                $row.find('.ce-is-child-course').each(function () {
                    var $checkbox = $(this);
                    var productId = $checkbox.data('product-id');
                    var postType = $checkbox.data('post-type');
                    var index = $checkbox.data('index');
                    var isChecked = $checkbox.is(':checked');

                    toggleParentCourseSelector(productId, postType, index, isChecked);
                });

                // Initialize select2 for this variation
                $row.find('.wc-enhanced-select').each(function () {
                    var $select = $(this);
                    if (!$select.hasClass('enhanced')) {
                        initEnhancedSelectForElement($select);
                    }
                });
            }, 100);
        });
    }

    /**
     * Initialize enhanced select for all parent course selects
     */
    function initEnhancedSelect() {
        $('.ce-parent-courses-select').each(function () {
            var $select = $(this);
            if (!$select.hasClass('enhanced')) {
                initEnhancedSelectForElement($select);
            }
        });
    }

    /**
     * Initialize enhanced select for a specific element
     *
     * @param {jQuery} $select Select element
     */
    function initEnhancedSelectForElement($select) {
        // Use WooCommerce's wc-enhanced-select if available
        if (typeof $.fn.select2 !== 'undefined') {
            var select2Args = {
                allowClear: true,
                placeholder: $select.data('placeholder') || 'Select any course',
                multiple: true,
                width: '50%'
            };

            // Check if this is an AJAX search field
            if ($select.data('action')) {
                select2Args.ajax = {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term,
                            action: $select.data('action'),
                            security: wc_enhanced_select_params ? wc_enhanced_select_params.search_nonce : ''
                        };
                    },
                    processResults: function (data) {
                        var terms = [];
                        if (data) {
                            $.each(data, function (id, text) {
                                terms.push({
                                    id: id,
                                    text: text
                                });
                            });
                        }
                        return {
                            results: terms
                        };
                    },
                    cache: true
                };
                select2Args.minimumInputLength = 3;
            }

            $select.select2(select2Args);
            $select.addClass('enhanced');
        }
    }

    /**
     * Toggle parent course selector visibility
     *
     * @param {number} productId Product ID
     * @param {string} postType  Post type
     * @param {number} index     Variation index
     * @param {boolean} show     Whether to show or hide
     */
    function toggleParentCourseSelector(productId, postType, index, show) {
        var wrapperId;

        if ('product_variation' === postType) {
            wrapperId = '#ce-parent-courses-wrapper-variation-' + index;
        } else {
            wrapperId = '#ce-parent-courses-wrapper';
        }

        var $wrapper = $(wrapperId);

        if ($wrapper.length) {
            if (show) {
                $wrapper.slideDown(200, function () {
                    // Initialize select2 when showing
                    var $select = $wrapper.find('.ce-parent-courses-select');
                    if ($select.length && !$select.hasClass('enhanced')) {
                        initEnhancedSelectForElement($select);
                    }
                });
            } else {
                $wrapper.slideUp(200);
            }
        }
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
