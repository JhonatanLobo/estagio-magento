define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'ko',
    'jquery',
    'mage/url',
    'mage/cookies',
    'require'
], function (Component, customerData, ko, $, urlBuilder, cookies, require) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Webjump_Jhonatan/sustos',
            ghostImageUrl: require.toUrl('Webjump_Jhonatan/images/fantasma-susto.png')
        },

        initialize: function () {
            this._super();

            var self = this;
            this.animando = ko.observable(false);
            this.easterEggVisivel = ko.observable(false);
            this.posicaoTop = ko.observable('35vh');
            this.posicaoLeft = ko.observable('75vw');

            this.timerAparicao = null;
            this.timerDesaparecer = null;
            this.timerAnimacao = null;

            this.sustosData = customerData.get('halloween-sustos');

            this.total = ko.computed(function () {
                var data = self.sustosData();
                return (data && typeof data.total !== 'undefined') ? data.total : 0;
            });

            this.mensagem = ko.computed(function () {
                var data = self.sustosData();
                return (data && data.mensagem) ? data.mensagem : '';
            });

            // Primeira aparição suave: aguarda de 12 a 16 segundos
            this.agendarProximaAparicao(Math.floor(Math.random() * 4000 + 12000));

            // Monitora incremento orgânico (ex: quando adiciona produto ao carrinho)
            var valorAnterior = (this.sustosData() && typeof this.sustosData().total !== 'undefined')
                ? this.sustosData().total
                : null;

            this.sustosData.subscribe(function (newData) {
                var novoTotal = (newData && typeof newData.total !== 'undefined') ? newData.total : 0;
                if (valorAnterior !== null && novoTotal > valorAnterior) {
                    self.acionarVooDoFantasma();
                }
                valorAnterior = novoTotal;
            });

            if (!this.sustosData() || Object.keys(this.sustosData()).length === 0) {
                customerData.reload(['halloween-sustos'], false);
            }

            return this;
        },

        sortearPosicao: function () {
            var topRandom = Math.floor(Math.random() * 50 + 20) + 'vh';
            var leftRandom = Math.floor(Math.random() * 65 + 15) + 'vw';
            this.posicaoTop(topRandom);
            this.posicaoLeft(leftRandom);
        },

        agendarProximaAparicao: function (tempoEsperaMs) {
            var self = this;

            if (this.timerAparicao) {
                clearTimeout(this.timerAparicao);
            }

            this.timerAparicao = setTimeout(function () {
                self.sortearPosicao();
                self.easterEggVisivel(true);

                if (self.timerDesaparecer) {
                    clearTimeout(self.timerDesaparecer);
                }

                self.timerDesaparecer = setTimeout(function () {
                    self.easterEggVisivel(false);
                    self.agendarProximaAparicao(Math.floor(Math.random() * 20000 + 35000));
                }, 7000);
            }, tempoEsperaMs);
        },

        clicarEasterEgg: function () {
            var self = this,
                targetUrl = urlBuilder.build('jhonatan/susto/assustar');

            if (this.timerDesaparecer) {
                clearTimeout(this.timerDesaparecer);
            }

            // Oculta o fantasminha imediatamente
            self.easterEggVisivel(false);

            // Aciona o voo do fantasma
            self.acionarVooDoFantasma();

            // Incrementa na sessão do Magento via AJAX
            $.ajax({
                url: targetUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: $.mage.cookies.get('form_key')
                }
            });

            // Reaparece em outro lugar com intervalo espaçado (40 a 60 segundos)
            self.agendarProximaAparicao(Math.floor(Math.random() * 20000 + 40000));
        },

        acionarVooDoFantasma: function () {
            var self = this;

            // TRAVA CRÍTICA: Se o fantasma já estiver voando na tela, ignora a segunda chamada!
            if (self.animando()) {
                return;
            }

            self.animando(true);

            if (self.timerAnimacao) {
                clearTimeout(self.timerAnimacao);
            }

            // 4.6 segundos de duração (sincronizado com os 4.5s do CSS)
            self.timerAnimacao = setTimeout(function () {
                self.animando(false);
                self.timerAnimacao = null;
            }, 4600);
        }
    });
});
