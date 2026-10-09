define([
    'ko',
    'mage/translate'
], function (ko, $t) {
    'use strict';

    return function (miniCartOriginal) {
        return miniCartOriginal.extend({
            initialize: function () {
                this._super();

                var self = this;

                this.mensagemAssombrada = ko.computed(function () {
                    var qtd = 0;

                    try {
                        if (typeof self.getCartParam === 'function') {
                            qtd = parseInt(self.getCartParam('summary_count'), 10) || 0;
                        }
                    } catch (e) {
                        qtd = 0;
                    }

                    if (qtd === 0) {
                        return $t('Seu caldeirao esta vazio');
                    }

                    return $t('Itens no caldeirao: ') + qtd;
                }, this);

                return this;
            }
        });
    };
});
