define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    var STORAGE_KEY = 'modo_assombrado';
    var BODY_CLASS = 'modo-assombrado-ativo';
    var TOGGLE_SELECTOR = '.modo-assombrado-toggle';

    function storageDisponivel() {
        try {
            var teste = '__modo_assombrado_test__';
            localStorage.setItem(teste, teste);
            localStorage.removeItem(teste);
            return true;
        } catch (e) {
            return false;
        }
    }

    function modoEstaAtivo() {
        if (!storageDisponivel()) {
            return false;
        }
        return localStorage.getItem(STORAGE_KEY) === '1';
    }

    function aplicarModo(ativo) {
        if (ativo) {
            $('body').addClass(BODY_CLASS);
        } else {
            $('body').removeClass(BODY_CLASS);
        }
    }

    function atualizarBotao() {
        var ativo = modoEstaAtivo();
        var $botao = $(TOGGLE_SELECTOR);

        if (!$botao.length) {
            return;
        }

        $botao.attr('aria-pressed', ativo ? 'true' : 'false');
        $botao.find('.modo-assombrado__label').text(
            ativo ? $t('Modo Normal') : $t('Modo Assombrado')
        );
    }

    function alternarModo(event) {
        if (event) {
            event.preventDefault();
        }

        var novoEstado = !modoEstaAtivo();

        if (storageDisponivel()) {
            localStorage.setItem(STORAGE_KEY, novoEstado ? '1' : '0');
        }

        aplicarModo(novoEstado);
        atualizarBotao();
    }

    function init() {
        aplicarModo(modoEstaAtivo());
        atualizarBotao();

        $(document).on('click', TOGGLE_SELECTOR, alternarModo);
    }

    $(init);

    return {
        alternar: alternarModo,
        isAtivo: modoEstaAtivo
    };
});
