# Implementação Técnica Magento 2: Contagem Regressiva Reativa em Knockout.js e Selo Assombrado de Catálogo

## 1. Visão Geral

Este repositório e Pull Request entregam o Desafio 17.1 (contagem regressiva e selo assombrado) da Sprint 8 (Magento 2 / Adobe Commerce). O trabalho consistiu em introduzir comportamento reativo assíncrono em JavaScript no tema de campanha **Noite Assombrada** (`Webjump/noite-assombrada`), com dois objetivos principais:

1. Desenvolver um componente de contagem regressiva em tempo real usando Knockout.js.
2. Estender a visibilidade de dados do catálogo com selos temáticos na listagem de produtos (PLP) e na página de detalhes do produto (PDP).

Tudo foi feito mantendo zero dependências externas e sem tocar no diretório `vendor/`.

A solução segue os padrões de frontend do ecossistema Magento:

- **Reatividade com Knockout.js e uiComponent:** componente frontend desacoplado estendendo `uiComponent`, usando `ko.observable` para o estado temporal e `ko.computed` para derivar a string formatada (dias, horas, minutos, segundos) a cada ciclo do relógio, sem recarregar a página.
- **Inicialização declarativa com `x-magento-init`:** o componente é instanciado via `<script type="text/x-magento-init">`, consumindo o ponto de entrada `Magento_Ui/js/core/app`, com carga assíncrona do template HTML (`web/template/contador.html`) sob escopo isolado (`data-bind="scope: 'halloweenCountdown'"`).
- **Tratamento defensivo de data passada:** verificação matemática que evita números negativos após o término da campanha (`2026-10-31 23:59:59`), comutando automaticamente para um estado expirado com ícone temático (👻) e mensagem amigável.
- **Renderização defensiva na PLP e PDP:** consumo dinâmico do atributo EAV booleano `selo_sustentavel`. A marcação do selo só é injetada quando o valor é ativo (`1`). Produtos sem o atributo (`0` ou `null`) não geram nós órfãos nem espaçamentos vazios no DOM.
- **Internacionalização e estilização integradas:** os termos do temporizador e do selo passam pelos dicionários `pt_BR.csv` e `en_US.csv`, e as regras de estilo ficam encapsuladas em `_extend.less`, respeitando os mixins responsivos (`.media-width`) da Magento UI Library.

## 2. Arquitetura e Organização de Arquivos

Árvore de artefatos no módulo (`src/app/code/Webjump/Jhonatan/`) e no tema filho (`src/app/design/frontend/Webjump/noite-assombrada/`):

```text
src/
├── app/
│   ├── code/
│   │   └── Webjump/
│   │       └── Jhonatan/
│   │           └── view/
│   │               └── frontend/
│   │                   ├── templates/
│   │                   │   └── contador.phtml                     # Inicialização declarativa via x-magento-init e escopo Knockout
│   │                   └── web/
│   │                       ├── js/
│   │                       │   └── contador.js                    # UI Component com ko.observable e ko.computed reativos
│   │                       └── template/
│   │                           └── contador.html                  # Template Knockout assíncrono (estados ativo e expirado)
│   │
│   └── design/
│       └── frontend/
│           └── Webjump/
│               └── noite-assombrada/
│                   ├── i18n/
│                   │   ├── en_US.csv                              # Dicionário de tradução da campanha (en_US)
│                   │   └── pt_BR.csv                              # Dicionário de tradução da campanha (pt_BR)
│                   ├── Magento_Catalog/
│                   │   ├── layout/
│                   │   │   └── catalog_product_view.xml           # Injeção do bloco do contador na coluna de compra da PDP
│                   │   └── templates/
│                   │       └── product/
│                   │           └── list.phtml                     # Sobrescrita com selo condicional nos cards
│                   ├── Magento_Cms/
│                   │   └── layout/
│                   │       └── cms_index_index.xml                # Injeção do bloco do contador sob page.top na Home
│                   ├── Webjump_Jhonatan/
│                   │   └── templates/
│                   │       └── product/
│                   │           └── badge.phtml                    # Sobrescrita visual do selo da PDP
│                   └── web/
│                       └── css/
│                           └── source/
│                               └── _extend.less                   # Estilização do contador, selos e breakpoints
│
README-17.1.md                                                     # Documentação técnica e matriz de homologação
```

## 3. Rastreabilidade dos Critérios de Aceite

| Requisito do Desafio | Solução Implementada | Status | Evidência |
|---|---|---|---|
| Contador atualiza sozinho sem recarregar a página | `contador.js` com `ko.observable` atualizado via `setInterval` e `ko.computed` reativo em tempo real. | Concluído | 01 e 04 |
| Tratamento de data final (sem números negativos) | Lógica de guarda temporal comutando para o bloco expirado com mensagem amigável. | Concluído | 07 e 08 |
| Inicialização declarativa por `x-magento-init` | Injeção no `contador.phtml` consumindo `Magento_Ui/js/core/app` e escopo `data-bind="scope: 'halloweenCountdown'"`. | Concluído | 03 |
| Selo exibido nos produtos marcados na listagem (PLP) | Injeção condicional no `list.phtml` do tema, renderizando o selo 🎃 Assombrado nos cards com atributo ativo. | Concluído | 05 |
| Selo exibido nos produtos marcados no detalhe (PDP) | Sobrescrita de `badge.phtml` aplicando a insígnia temática alinhada à esquerda, acima do preço. | Concluído | 04 |
| Produto sem o atributo não gera erro nem espaço vazio | Condicional defensiva `if ((bool)$_product->getData('selo_sustentavel'))` suprimindo o nó do DOM. | Concluído | 06 |
| Textos do contador e do selo passam pelo CSV de tradução | Termos envolvidos com `$t` no JavaScript e `$escaper->escapeHtml(__())` no PHP, mapeados em `pt_BR.csv` e `en_US.csv`. | Concluído | 09 |
| Responsividade móvel em conformidade | Mixins `.media-width` no `_extend.less` adaptando temporizador e selos para telas ≤768px. | Concluído | 10 |
| Inviolabilidade do core e compilação limpa | `setup:di:compile` finalizado em 100% e `git status` atestando `vendor/` e tema pai Luma intocados. | Concluído | 11 e 12 |

## 4. Passo a Passo do Desenvolvimento

### Passo 1: UI Component reativo (`contador.js`)

- Criei o componente JavaScript estendendo `uiComponent` no módulo `Webjump_Jhonatan`.
- Declarei `segundosRestantes` como `ko.observable`, inicializado pelo cálculo da diferença entre o timestamp atual e a data alvo (`2026-10-31 23:59:59`).
- Implementei o observável computado `expirado` para controle de estado e `mensagemContador` para derivar dias, horas, minutos e segundos.
- Configurei o temporizador via `setInterval` a cada 1000ms, com limpeza automática via `clearInterval` assim que o tempo restante se esgota.
- Mapeei a propriedade `labelContador` consumindo a função de internacionalização `mage/translate` (`$t`) para evitar parsing complexo no HTML.

### Passo 2: Templates assíncronos (`contador.html` e `contador.phtml`)

- Estruturei o template Knockout `contador.html` com blocos de comentários virtuais (`<!-- ko ifnot: expirado -->` e `<!-- ko if: expirado -->`), garantindo alternância limpa entre os estados.
- Associei as saídas de texto via `data-bind="text: labelContador"` e `data-bind="text: mensagemContador"`.
- Criei o template de inicialização `contador.phtml`, com a div `data-bind="scope: 'halloweenCountdown'"` carregando o template remoto via `getTemplate()` e disparando a inicialização declarativa por `x-magento-init` apontando para `Magento_Ui/js/core/app`.

### Passo 3: Injeção declarativa nos layouts XML (Home e PDP)

- **Home (`Magento_Cms/layout/cms_index_index.xml`):** injetei o bloco do temporizador dentro de `page.top`, logo após a faixa promocional da campanha (`after="halloween.faixa"`).
- **PDP (`Magento_Catalog/layout/catalog_product_view.xml`):** injetei o bloco do temporizador dentro de `product.info.main`, antes do bloco de preço (`before="product.info.price"`), na área de decisão de compra.

### Passo 4: Sobrescrita de catálogo e selos (PLP e PDP)

- Copiei o template original `vendor/magento/module-catalog/view/frontend/templates/product/list.phtml` para o tema filho em `Magento_Catalog/templates/product/list.phtml`.
- Inseri a checagem defensiva `if ((bool)$_product->getData('selo_sustentavel'))` antes do título do produto, adicionando o selo 🎃 Assombrado.
- Criei a sobrescrita em `Webjump_Jhonatan/templates/product/badge.phtml`, estilizando o selo da PDP com o texto "Produto Assombrado".

### Passo 5: i18n e LESS

- Adicionei os termos temáticos nos arquivos `i18n/pt_BR.csv` e `i18n/en_US.csv`.
- Atualizei o `web/css/source/_extend.less` com estilos para o contador (`.halloween-countdown`), alinhamento específico da coluna da PDP (`justify-content: flex-start; text-align: left;`), badges de catálogo e regras de responsividade mobile sob `.media-width`.

## 5. Troubleshooting: Problemas Encontrados e Soluções

### Erro de sintaxe de binding no Knockout (`SyntaxError: Unexpected string` em `i18n: {...}`)

**Sintoma:** ao carregar a Home, o componente não renderizava a contagem e exibia os emojis 🎃 e 👻 soltos na tela. O console apontava `SyntaxError: Unexpected string` e falha de carga do template `Webjump_Jhonatan/contador`.

**Causa:** o template continha `data-bind="i18n: {'Ofertas assombrosas encerram em:'}"`. As chaves `{ ... }` geravam uma expressão inválida para o avaliador sintático do Knockout.

**Solução:** movi a string para `this.labelContador = $t('Ofertas assombrosas encerram em:');` no JavaScript. No HTML, a vinculação ficou apenas como `data-bind="text: labelContador"`.

### `ReferenceError: segundos is not defined` no temporizador

**Sintoma:** o Knockout interrompia o processamento do observável computado e mantinha os campos numéricos vazios.

**Causa:** no método `mensagemContador` de `contador.js`, o cálculo de segundos foi atribuído à variável `seg` (`var seg = s % 60;`), mas a chamada de formatação referenciava `pad(segundos)`.

**Solução:** corrigi a declaração para `var segundos = s % 60;`, garantindo a formatação de dois dígitos.

### Desalinhamento na PDP

**Sintoma:** o bloco do temporizador renderizava centralizado no meio da coluna de compra da PDP, desalinhado com o título, o selo e o preço (que estavam à esquerda).

**Causa:** o contêiner `.halloween-countdown-wrapper` tinha a regra global `justify-content: center;`, criada para o topo da Home.

**Solução:** adicionei uma regra de alta especificidade no `_extend.less` sob `.catalog-product-view .product-info-main .halloween-countdown-wrapper`, forçando `justify-content: flex-start; text-align: left;`. A centralização da Home permaneceu intacta.

### Layout XML ignorado na Home

**Sintoma:** as injeções de layout para a página inicial posicionadas sob `Magento_Theme/layout/` eram ignoradas pelo resolvedor de layout.

**Causa:** a rota da Home (`cms_index_index`) pertence ao módulo `Magento_Cms`. No mecanismo de fallback do Magento 2, o layout precisa respeitar o diretório do módulo correspondente.

**Solução:** movi o arquivo para o caminho correto: `src/app/design/frontend/Webjump/noite-assombrada/Magento_Cms/layout/cms_index_index.xml`.

## 6. Decisões de Engenharia Frontend

### Por que `uiComponent` e `x-magento-init` em vez de scripts inline com jQuery?

A arquitetura de UI Components do Magento desacopla o comportamento do HTML cacheado pelo Full Page Cache (FPC). Com `x-magento-init`, a execução ocorre após o carregamento assíncrono das dependências pelo RequireJS, em conformidade com as diretivas de Content Security Policy (CSP) e sem bloquear a renderização do DOM.

### Por que tratar datas expiradas no observável computado em vez de ocultar via CSS?

Ocultar o elemento com CSS manteria o temporizador rodando em memória e preservaria nós com números negativos na árvore de acessibilidade. Tratar na camada de lógica do Knockout (`expirado`) interrompe o `setInterval`, zera o consumo de CPU e comuta a interface semântica para tecnologias assistivas (`role="timer"`, `aria-live="polite"`).

### Por que herdar e sobrescrever o `list.phtml` no tema em vez de alterar o módulo da Sprint 7?

A camada de apresentação do catálogo é responsabilidade do tema. Copiar o arquivo integralmente preserva os scripts de submissão ao carrinho (`catalogAddToCart`), os renderizadores de preço e as galerias nativas do tema pai Luma, inserindo de forma cirúrgica e defensiva a insígnia apenas nos itens marcados.

## 7. Relatório de Quality Assurance (QA)

### Evidência 01 — Temporizador ativo na Home (Desktop)

Home Page em tela cheia. O bloco `.halloween-countdown` renderiza centralizado sob a faixa promocional, com o ícone 🎃, a legenda traduzida e o relógio decrementando os segundos ao vivo (`22d 20h 32m 21s`).

![Evidência 01](https://github.com/user-attachments/assets/936fb7e4-1974-4eae-a89b-6ead3c0d6239)

### Evidência 02 — Carregamento assíncrono de recursos (DevTools Network)

Aba Network do Chrome DevTools filtrada por `contador`. Comprova as requisições assíncronas de `contador.js` (script via RequireJS) e `contador.html` (XHR via Knockout), ambas com status HTTP 200.

![Evidência 02](https://github.com/user-attachments/assets/74f41bff-7829-4c76-8cc3-d75b78152ab6)

### Evidência 03 — Inicialização declarativa no DOM e console limpo

Aba Elements com o contêiner `<div class="halloween-countdown-wrapper" data-bind="scope: 'halloweenCountdown'">` e o comentário virtual `<!-- ko ifnot: expirado -->` resolvido com valores numéricos. Console sem erros em vermelho.

![Evidência 03](https://github.com/user-attachments/assets/a574e76c-5576-4fc5-8449-e31ace4f707e)

### Evidência 04 — PDP: temporizador e selo integrados

PDP do Mouse Gamer Pro Wireless. A coluna `.product-info-main` exibe o selo 🎃 PRODUTO ASSOMBRADO e a caixa do temporizador alinhados à esquerda, na mesma linha de base vertical acima do bloco de preço (US$ 299,90).

![Evidência 04](https://github.com/user-attachments/assets/b906cc97-d2fe-4ecd-8b30-bc5fb59532a9)

### Evidência 05 — PLP: card com selo «Assombrado»

Listagem de Periféricos (`/perifericos.html`). O card exibe o selo temático 🎃 ASSOMBRADO com fundo abóbora (`#FF6B1A`), tipografia em caixa alta e cantos arredondados, acima do nome do item.

![Evidência 05](https://github.com/user-attachments/assets/0d68d99a-9941-4216-8c9d-01589a6bb8f1)

### Evidência 06 — Programação defensiva: item sem atributo

Inspeção de elementos com o atributo `selo_sustentavel` desativado (`0`). A árvore HTML de `.product-item-details` comprova a supressão total da classe `.halloween-product-badge`, sem nós órfãos nem espaçamentos vazios.

![Evidência 06](https://github.com/user-attachments/assets/434e065d-a8d5-42f8-a3b0-da6d923f4ee4)

### Evidência 07 — Cenário de borda na Home: contagem expirada

Home Page com data simulada posterior ao fim da campanha (`2026-11-01 00:00:00`). O componente desativa o relógio ativo e apresenta o bloco `.halloween-countdown__expired` com o ícone 👻 e a mensagem de encerramento, sem exibir números negativos.

![Evidência 07](https://github.com/user-attachments/assets/a13077e8-f0ea-4105-a994-5013cd8f44b3)

### Evidência 08 — Cenário de borda na PDP: contagem expirada

PDP sob o mesmo cenário de expiração temporal. A coluna de compra exibe a mensagem de encerramento com o fantasma 👻, preservando o alinhamento à esquerda e a integridade dos botões de conversão.

![Evidência 08](https://github.com/user-attachments/assets/901315fd-6650-4fb5-832d-59f1ca48709f)

### Evidência 09 — Internacionalização nos arquivos de tradução (CLI)

Leitura no terminal via `tail` dos arquivos `pt_BR.csv` e `en_US.csv`. Comprova o registro das expressões "Ofertas assombrosas encerram em:", "Assombrado" e "Produto Assombrado" na camada i18n do tema.

![Evidência 09](https://github.com/user-attachments/assets/58ed5e3f-e2d1-4bc0-be5e-c18386de7bcd)

### Evidência 10 — Responsividade em dispositivo móvel

Emulação móvel no DevTools (iPhone 16 Pro Max / 440px). O temporizador e o selo adaptam-se proporcionalmente à largura da tela através do mixin `.media-width`, sem quebra horizontal nem sobreposição de texto.

![Evidência 10](https://github.com/user-attachments/assets/cc9a7d34-21a4-489f-a11b-6601471c4c61)

### Evidência 11 — Compilação limpa de injeção de dependências (CLI)

Execução de `bin/magento setup:di:compile` no WSL2, finalizada em 100% de progresso (`Generated code and dependency injection configuration successfully.`), sem quebras de contrato nem erros de sintaxe.

![Evidência 11](https://github.com/user-attachments/assets/80ecae02-8d11-448f-9d60-6087f999fea4)

### Evidência 12 — Inviolabilidade do core e árvore Git (CLI)

Saída de `git status -uall` na branch `exercicio/17.1-contador-selo-assombrado`. As modificações ficaram restritas a arquivos customizados do módulo e do tema filho, confirmando que `vendor/` e o tema pai Luma permaneceram intocados.

![Evidência 12](https://github.com/user-attachments/assets/b937e0a5-e752-4e0c-ab02-893ecd582d0c)