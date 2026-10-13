define([
    'ko',
    'jquery'
], function (ko, $) {
    'use strict';

    if (!ko.bindingHandlers.tremor) {
        ko.bindingHandlers.tremor = {
            init: function (element, valueAccessor) {
                var $element = $(element),
                    timerId = null;

                function getOptions() {
                    var options = ko.unwrap(valueAccessor()) || {};

                    return {
                        intensity: options.intensity || 'media',
                        duration: parseInt(options.duration, 10) || 800,
                        trigger: options.trigger || 'hover'
                    };
                }

                function triggerTremor() {
                    var opts = getOptions(),
                        animationClass = 'spooky-tremor--' + opts.intensity;

                    if (timerId) {
                        clearTimeout(timerId);
                        $element.removeClass('spooky-tremor--baixa spooky-tremor--media spooky-tremor--alta spooky-tremor-active');
                    }

                    // Ativa a vibracao rapida
                    $element.addClass('spooky-tremor-active ' + animationClass);

                    // Desativa o efeito ao atingir a duracao total configurada
                    timerId = setTimeout(function () {
                        $element.removeClass('spooky-tremor-active ' + animationClass);
                        timerId = null;
                    }, opts.duration);
                }

                var initialOpts = getOptions();

                if (initialOpts.trigger === 'hover') {
                    $element.on('mouseenter.spookyTremor', triggerTremor);
                } else if (initialOpts.trigger === 'click') {
                    $element.on('click.spookyTremor', triggerTremor);
                } else if (initialOpts.trigger === 'load') {
                    triggerTremor();
                }

                ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                    if (timerId) {
                        clearTimeout(timerId);
                    }
                    $element.off('.spookyTremor');
                });
            },

            update: function (element, valueAccessor) {
                ko.unwrap(valueAccessor());
            }
        };
    }

    return ko.bindingHandlers.tremor;
});
