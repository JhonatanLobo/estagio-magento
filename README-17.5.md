# Implementação Técnica Magento 2: Custom Binding Próprio no Knockout.js

## 1. Visão Geral do Entregável

Este repositório e Pull Request formalizam a entrega técnica do Desafio 17.5 (Binding próprio do Knockout) da Sprint 8 (Magento 2 / Adobe Commerce). O objetivo central consistiu em desenvolver um custom binding declarativo (`ko.bindingHandlers.tremor`) no ecossistema Knockout.js da plataforma, permitindo injetar microinterações visuais de tremor temático de Halloween em qualquer elemento HTML da loja diretamente via atributos de template, sem replicação de scripts.

A solução atende aos seguintes pilares de engenharia de software e padrões corporativos:

- **Desacoplamento e Reutilização Transversal:** O manipulador foi concebido como um binding reutilizável registrado globalmente via RequireJS, sendo consumido de forma declarativa em múltiplos componentes assíncronos (`contador.html` e `minicart/content.html`) com parametrizações dinâmicas de intensidade e duração.
- **Ciclo de Vida Limpo e Prevenção de Vazamento de Memória (Memory Leak):** Implementação rigorosa do método `init` com registro de listeners via namespace jQuery (`.spookyTremor`) e limpeza determinística através de `ko.utils.domNodeDisposal.addDisposeCallback`, garantindo o cancelamento de temporizadores e remoção de eventos quando os nós do DOM são descartados pelo Knockout.
- **Física da Animação Desacoplada da Duração:** Separação entre a frequência de oscilação do tremor (ciclo rápido de 120ms via CSS `@keyframes`) e a duração total do efeito controlada por `setTimeout` no JavaScript, proporcionando feedback visual fluído e nítido.
- **Isolamento de Responsabilidades:** O módulo (`Webjump_Jhonatan`) fornece a lógica comportamental do binding, enquanto o tema filho (`Webjump/noite-assombrada`) é responsável pela estilização estética das animações no `_extend.less`, preservando a inviolabilidade do diretório `vendor/`.

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore de arquivos desenvolvidos e estendidos no módulo backend (`src/app/code/Webjump/Jhonatan/`) e no tema customizado (`src/app/design/frontend/Webjump/noite-assombrada/`):

```plaintext
src/
├── app/
│   ├── code/
│   │   └── Webjump/
│   │       └── Jhonatan/
│   │           └── view/
│   │               └── frontend/
│   │                   ├── requirejs-config.js                     # Injeção global do binding na seção deps
│   │                   ├── web/
│   │                   │   ├── js/
│   │                   │   │   └── bindings/
│   │                   │   │       └── tremor.js                   # Custom Binding Handler (ko.bindingHandlers.tremor)
│   │                   │   └── template/
│   │                   │       └── contador.html                   # Consumo do binding no ícone do contador (Lugar 1)
│   │
│   └── design/
│       └── frontend/
│           └── Webjump/
│               └── noite-assombrada/
│                   ├── Magento_Checkout/
│                   │   └── web/
│                   │       └── template/
│                   │           └── minicart/
│                   │               └── content.html                # Consumo do binding na mensagem do minicart (Lugar 2)
│                   └── web/
│                       └── css/
│                           └── source/
│                               └── _extend.less                    # Regras @keyframes e classes utilitárias do tremor
│
README-17.5.md                                                      # Documentação técnica e matriz de homologação
```

## 3. Rastreabilidade dos Critérios de Aceite

A tabela abaixo correlaciona os requisitos da especificação do Desafio 17.5 com as soluções técnicas desenvolvidas e as respectivas evidências de QA:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
|---|---|---|---|
| O binding está registrado e funciona ao ser declarado no template | Criação de `ko.bindingHandlers.tremor` carregado automaticamente via `deps` no `requirejs-config.js`. | Concluído | Evidências 01a, 01b, 05 e 06 |
| Aceita parâmetros e o comportamento muda conforme eles | Suporte dinâmico aos parâmetros `intensity` ('baixa', 'media', 'alta'), `duration` (ms) e `trigger` ('hover', 'click', 'load'). | Concluído | Evidências 01a, 01b, 02, 03 e 07 |
| Foi usado em dois lugares distintos, sem duplicar código | Injetado no ícone do contador regressivo (`contador.html`) e no aviso de caldeirão do minicart (`content.html`). | Concluído | Evidências 01a, 01b, 02 e 03 |
| README explica quando vale criar binding próprio em vez de componente | Justificativa técnica completa detalhando os critérios de decisão arquitetural. | Concluído | Seção 6 deste documento |
| Integridade de Runtime e Console | Execução livre de erros de compilação Knockout, RequireJS ou sintaxe (0 errors). | Concluído | Evidência 04 |
| Inviolabilidade do Núcleo e Compilação Limpa | Execução bem-sucedida de `setup:di:compile` e `git status` atestando `vendor/` e tema Luma intocados. | Concluído | Evidência 08 |

## 4. Passo a Passo do Processo de Desenvolvimento

### Passo 1: Construção do Custom Binding Handler (`tremor.js`)

Criou-se o arquivo `src/app/code/Webjump/Jhonatan/view/frontend/web/js/bindings/tremor.js`, estendendo o objeto global `ko.bindingHandlers.tremor`.

- No método `init`, implementou-se a função auxiliar `getOptions()` utilizando `ko.unwrap(valueAccessor())` para permitir a leitura transparente tanto de objetos literais quanto de parâmetros observáveis (observables).
- A função `triggerTremor()` gerencia a adição e remoção das classes CSS temáticas (`spooky-tremor--alta`, `spooky-tremor--media`, `spooky-tremor--baixa`) e o temporizador `setTimeout` parametrizado.
- Registrou-se a limpeza determinística com `ko.utils.domNodeDisposal.addDisposeCallback(element, ...)`, garantindo que, caso o elemento seja destruído da árvore do DOM, todos os temporizadores ativos e eventos do namespace `.spookyTremor` sejam expurgados.

### Passo 2: Registro Global via RequireJS (`requirejs-config.js`)

Atualizou-se o arquivo `requirejs-config.js` do módulo adicionando o caminho do binding ao array `deps`:

```javascript
deps: [
    'Webjump_Jhonatan/js/modo-assombrado',
    'Webjump_Jhonatan/js/bindings/tremor'
]
```

Isso garante que o motor do Knockout registre o binding `data-bind="tremor: ..."` antes da renderização de qualquer template da loja.

### Passo 3: Engenharia de Animações CSS no Tema (`_extend.less`)

Declararam-se as diretivas `@keyframes` para as três intensidades (`spooky-tremor-baixa`, `spooky-tremor-media` e `spooky-tremor-alta`), aplicando variações de deslocamento angular (`rotate`) e translação bidimensional (`translate`).

A classe base `.spooky-tremor-active` recebeu `animation-duration: 120ms !important;` e `animation-iteration-count: infinite !important;` para assegurar uma taxa de oscilação rápida e constante.

### Passo 4: Aplicação Declarativa no Contador Regressivo (Lugar 1)

No template `contador.html`, o ícone do emoji ativo 🎃 recebeu o binding com intensidade alta e ciclo de 600 milissegundos:

```html
<span class="halloween-countdown__icon"
      aria-hidden="true"
      data-bind="tremor: { intensity: 'alta', duration: 600, trigger: 'hover' }">&#x1F383;</span>
```

### Passo 5: Aplicação Declarativa no Minicart (Lugar 2)

No template sobrescrito `content.html` do minicart, a faixa `.mensagem-assombrada` recebeu o binding com intensidade média e ciclo de 1000 milissegundos:

```html
<div class="mensagem-assombrada"
     data-bind="text: mensagemAssombrada, tremor: { intensity: 'media', duration: 1000, trigger: 'hover' }"
     role="status"
     aria-live="polite"></div>
```

### Passo 6: Pipeline de Compilação e Deploy Estático

Executou-se a limpeza de arquivos pré-processados (`var/view_preprocessed/` e `pub/static/`) e a compilação de estáticos via CLI (`bin/magento setup:static-content:deploy -f pt_BR en_US`).

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: Física da Animação Arrastada e Percepção Lenta de Tremor

**Sintoma:** Ao acionar o evento de hover, o elemento realizava um movimento lento, flutuante e arrastado em vez de uma vibração enérgica de susto.

**Causa Raiz:** A propriedade CSS `animation-duration` estava sendo alimentada diretamente com o valor total da opção `duration` configurada (ex.: 1000ms). Como os `@keyframes` possuíam apenas 5 posições, o navegador levava um segundo inteiro para executar uma única oscilação.

**A Solução:** Desacoplou-se a velocidade da oscilação do tempo de vida total da animação. Fixou-se a taxa de ciclo em 120ms no CSS (`animation-duration: 120ms !important; animation-iteration-count: infinite;`), enquanto o parâmetro `duration` do JavaScript passou a controlar exclusivamente o `setTimeout` que remove as classes ativas, gerando uma vibração nítida e responsiva.

### O Problema: Colapso de Layout e Espremimento na Faixa do Minicart

**Sintoma:** Ao passar o mouse sobre a mensagem do minicart, o elemento colapsava horizontalmente, perdendo a largura total e espremendo os botões de controle e valores monetários.

**Causa Raiz:** A classe utilitária `.spooky-tremor-active` possuía uma regra genérica `display: inline-block !important;`. No layout nativo do Luma, o cabeçalho do minicart opera com elementos flutuantes (`float: left` e `float: right`). A conversão forçada da `div` para `inline-block` causava perda de quebra de linha.

**A Solução:** No `_extend.less`, isolou-se a regra de `inline-block` para tags nativamente em linha (`span`, `a`) e forçou-se a estrutura em bloco da mensagem:

```less
.minicart-wrapper {
    .mensagem-assombrada {
        display: block !important;
        clear: both !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
}
```

## 6. Decisões de Engenharia Frontend: Binding Próprio vs. UI Component

A arquitetura frontend do Magento 2 oferece dois caminhos principais para estender o comportamento da loja com JavaScript: Custom Bindings e UI Components (ambos integrados ao Knockout.js). A escolha técnica deve considerar os seguintes critérios:

### Quando vale a pena criar um Custom Binding?

- **Comportamento Puramente Visual e Manipulação do DOM:** Casos de uso onde o objetivo primário é atuar diretamente sobre nós HTML existentes (aplicar animações temporizadas, efeitos de tremor, máscaras em campos de formulário, inicializar tooltips ou bibliotecas leves como drag-and-drop).
- **Reutilização Transversal sem Estado Próprio:** Quando o comportamento deve ser injetado em múltiplos templates distintos já existentes sem a necessidade de instanciar novos componentes ou gerenciar modelos de dados isolados.
- **Desacoplamento e Performance:** Bindings não instanciam árvores de componentes inteiras nem exigem definições XML de layout, apresentando uma pegada de memória e tempo de execução significativamente menores.

### Quando vale a pena criar um UI Component?

- **Gerenciamento de Estado Complexo:** Quando o elemento precisa manter observables próprios, computed properties interdependentes e controlar seu ciclo de vida em memória.
- **Templates Dinâmicos e Inicialização Isolada:** Quando o componente precisa carregar arquivos `.html` assíncronos sob demanda através de `getTemplate()`, ou precisa ser instanciado via XML de layout ou `<script type="text/x-magento-init">`.
- **Comunicação com Backend e Sessão:** Componentes que consomem APIs REST/GraphQL ou se comunicam com o armazenamento do cliente via `customerData` (como o minicart ou o contador regressivo).

**Decisão neste desafio:** O efeito de tremor é um comportamento visual transversal e sem estado de negócio. Criar um UI Component exclusivo para tremer um elemento adicionaria complexidade excessiva (overengineering). O Custom Binding demonstrou ser a solução mais elegante, reutilizável e aderente às boas práticas do ecossistema.

## 7. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as evidências formais de homologação comprovando a execução do tremor dinâmico em múltiplos componentes, a parametrização em tempo real e a integridade de compilação:

### Evidência 01a — Demonstração Dinâmica no Contador Regressivo (GIF)

![Evidência 01a](https://github.com/user-attachments/assets/745214de-44cf-4fc3-9aef-29c8016640ee)

**Análise Visual/Técnica:** Registro em vídeo/GIF do cursor passando sobre o ícone 🎃 do contador regressivo. Comprova a oscilação rápida com intensidade alta (`alta`) durante 600ms e a interrupção estática suave após o término do temporizador.

### Evidência 01b — Demonstração Dinâmica no Minicart (GIF)

![Evidência 01b](https://github.com/user-attachments/assets/18d73297-bc7d-40dc-870e-97a8636f962c)

**Análise Visual/Técnica:** Registro em vídeo/GIF da interação com a barra `.mensagem-assombrada` no minicart. Comprova a ativação do mesmo binding em um contexto completamente distinto, operando com intensidade média (`media`) e duração de 1000ms sem deformar a largura do contêiner.

### Evidência 02 — Inspeção do Atributo no Contador (DevTools Elements)

![Evidência 02](https://github.com/user-attachments/assets/1d8d6899-ba8d-499a-af42-7393cd6b38f5)

**Análise Visual/Técnica:** Aba Elements do DevTools inspecionando a tag `<span>` da classe `.halloween-countdown__icon`, evidenciando a declaração explícita do atributo `data-bind="tremor: { intensity: 'alta', duration: 600, trigger: 'hover' }"`.

### Evidência 03 — Inspeção do Atributo no Minicart (DevTools Elements)

![Evidência 03](https://github.com/user-attachments/assets/c850fbb5-5c74-4506-aa42-8da69cda655d)

**Análise Visual/Técnica:** Aba Elements do DevTools com o minicart aberto, destacando a tag `<div class="mensagem-assombrada">` selecionada no inspetor no momento da interação, evidenciando o atributo de binding e a injeção dinâmica das classes de animação `spooky-tremor-active spooky-tremor--media`.

### Evidência 04 — Console do Navegador Limpo (DevTools Console)

![Evidência 04](https://github.com/user-attachments/assets/d3ebab59-9f47-4c70-97a8-26e3967bd838)

**Análise Visual/Técnica:** Aba Console do DevTools após a navegação e disparo das animações em ambos os componentes, atestando conformidade estrita de runtime com zero erros (No errors) de JavaScript ou Knockout.

### Evidência 05 — Injeção Global no RequireJS (CLI)

![Evidência 05](https://github.com/user-attachments/assets/5f62ea62-0b9a-4ce1-b1c4-5730b4f352a3)

**Análise Visual/Técnica:** Leitura no terminal via `cat` do arquivo `requirejs-config.js`, comprovando o registro do caminho `Webjump_Jhonatan/js/bindings/tremor` dentro do array `deps`.

### Evidência 06 — Código do Custom Binding Handler (CLI)

![Evidência 06](https://github.com/user-attachments/assets/f27dd422-9f17-48d2-a5d3-4d72b2911ab4)

**Análise Visual/Técnica:** Visualização do código-fonte de `tremor.js` no terminal, destacando a declaração em `ko.bindingHandlers.tremor`, os métodos de ciclo de vida (`init`, `update`) e o descarte defensivo de memória com `domNodeDisposal`.

### Evidência 07 — Keyframes e Estilização no LESS do Tema (CLI)

![Evidência 07](https://github.com/user-attachments/assets/7c756d4f-6063-4999-a890-579a0c3378dc)

**Análise Visual/Técnica:** Exibição do trecho final de `_extend.less` no terminal, demonstrando as regras de `@keyframes` para as intensidades (`baixa`, `media`, `alta`) com taxa de ciclo em 120ms e o isolamento estrutural para o minicart.

### Evidência 08 — Compilação e Inviolabilidade do Core (CLI)

![Evidência 08](https://github.com/user-attachments/assets/21a0a281-51cc-4e16-935f-51cff3922633)

**Análise Visual/Técnica:** Execução de `setup:di:compile && git status -uall && git status --porcelain vendor/`, comprovando compilação finalizada em 100%, branch limpa contendo apenas os artefatos do desafio e zero arquivos modificados na pasta `vendor/`.