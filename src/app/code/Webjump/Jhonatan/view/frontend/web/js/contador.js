define([
    'uiComponent',
    'ko',
    'mage/translate'
], function (Component, ko, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Webjump_Jhonatan/contador',
            dataFinal: '2026-10-31 23:59:59',
            mensagemExpirada: 'A noite de Halloween chegou! As ofertas assombrosas foram encerradas.'
        },

        initialize: function () {
            this._super();

            var self = this;
            var dataAlvo = self.dataFinal || (self.defaults && self.defaults.dataFinal) || '2026-10-31 23:59:59';
            self.timestampAlvo = new Date(dataAlvo.replace(/-/g, '/')).getTime();

            self.labelContador = $t('Ofertas assombrosas encerram em:');

            self.segundosRestantes = ko.observable(self.calcularSegundosRestantes());

            self.expirado = ko.computed(function () {
                return self.segundosRestantes() <= 0;
            });

            self.mensagemContador = ko.computed(function () {
                var s = self.segundosRestantes();

                if (s <= 0) {
                    var msg = self.mensagemExpirada || (self.defaults && self.defaults.mensagemExpirada);
                    return $t(msg);
                }

                var dias = Math.floor(s / 86400);
                var horas = Math.floor((s % 86400) / 3600);
                var minutos = Math.floor((s % 3600) / 60);
                var segundos = s % 60;

                var pad = function (n) {
                    return n < 10 ? '0' + n : n;
                };

                return dias + 'd ' + pad(horas) + 'h ' + pad(minutos) + 'm ' + pad(segundos) + 's';
            });

            if (self.segundosRestantes() > 0) {
                self.temporizador = setInterval(function () {
                    var restante = self.calcularSegundosRestantes();
                    self.segundosRestantes(restante);

                    if (restante <= 0) {
                        clearInterval(self.temporizador);
                    }
                }, 1000);
            }

            return self;
        },

        calcularSegundosRestantes: function () {
            var agora = new Date().getTime();
            var diferenca = Math.floor((this.timestampAlvo - agora) / 1000);

            return diferenca > 0 ? diferenca : 0;
        }
    });
});