# Implementação Técnica Magento 2: Primeiro Módulo com Bloco e ViewModel na Home

## 1. Visão Geral do Entregável

Este documento formaliza a entrega técnica do **Desafio 13.1 (Primeiro módulo com bloco na home)** da Sprint 6 (Magento 2 / Adobe Commerce). O objetivo principal foi arquitetar e desenvolver um módulo customizado desacoplado do núcleo da plataforma, estabelecendo uma barra institucional de compromissos e credenciais na página inicial da loja sem alterar nenhum arquivo nativo do diretório `vendor/`.

A solução foi projetada sob os padrões modernos de engenharia do Magento 2, contemplando cinco pilares arquiteturais:

1. **Modularidade e Governança (`ComponentRegistrar`):** Registro e declaração formal do componente `Webjump_Jhonatan` via `registration.php` e `etc/module.xml`, com declaração explícita de dependências na diretiva `<sequence>`.
2. **Separação Estrita de Responsabilidades (Padrão ViewModel):** Abstração de toda a lógica e modelagem dos dados comerciais na classe `HomeBlock`, implementando `ArgumentInterface` e eliminando a dependência do modelo legado de `Block`.
3. **Orquestração Declarativa via Layout XML (`cms_index_index`):** Injeção do bloco no container semântico `content` da Home e carregamento seletivo de estilos através do processador nativo de layouts.
4. **Programação Defensiva e Blindagem contra XSS:** Sanitização integral de todas as saídas de dados no template `.phtml` utilizando `$block->escapeHtml()` para textos e `$block->escapeHtmlAttr()` para atributos de paths SVG, acompanhada de validação de não-nulidade para evitar nós vazios no DOM.
5. **Isolamento de UI e Design Corporativo:** Implementação de folha de estilo dedicada em `view/frontend/web/css/home-block.css`, com layout responsivo em CSS Grid, tipografia balanceada e ícones vetoriais inline.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore completa de diretórios construída dentro do ecossistema do Magento 2 (`src/app/code/Webjump/Jhonatan/`), destacando a separação de responsabilidades de cada camada:

```text
src/app/code/Webjump/Jhonatan/
├── registration.php                                       # Registro do módulo no ComponentRegistrar do Magento
├── etc/
│   └── module.xml                                         # Declaração do módulo e sequência de carregamento (Core)
│
├── ViewModel/
│   └── HomeBlock.php                                      # Lógica de dados, contratos de serviço e validação defensiva
│
└── view/
    └── frontend/
        ├── layout/
        │   └── cms_index_index.xml                        # Injeção declarativa do bloco e folha de estilo na Home
        ├── templates/
        │   └── home_block.phtml                           # Template de apresentação semântico e sanitizado
        └── web/
            └── css/
                └── home-block.css                         # Estilização isolada e responsiva do componente

```

---

## 3. Rastreabilidade dos Critérios de Aceite

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
| --- | --- | --- | --- |
| **Módulo Ativo no Sistema** | Criação de `registration.php` e `module.xml`, ativando o componente via CLI (`module:status`). | **Concluído** | Evidência 01 |
| **Bloco Renderizado na Home** | Injeção no container `content` através do handle `cms_index_index.xml` via Layout XML. | **Concluído** | Evidência 02 |
| **Lógica Isolada no ViewModel** | Implementação de `HomeBlock` sob `ArgumentInterface`, mantendo o template 100% livre de regras. | **Concluído** | Evidência 03 |
| **Saída Estritamente Escapada** | Aplicação obrigatória de `$block->escapeHtml()` e `$block->escapeHtmlAttr()` em todos os nós. | **Concluído** | Evidência 03 |
| **Estilização Própria em CSS** | Folha `home-block.css` declarada no `<head>` do layout e carregada via HTTP 200. | **Concluído** | Evidência 04 |
| **Documentação da Arquitetura** | Mapeamento integral da estrutura de diretórios e ciclo de execução no README do projeto. | **Concluído** | Seções 2 e 6 deste doc |

---

## 4. Passo a Passo do Processo de Desenvolvimento

A construção do módulo seguiu uma esteira lógica para assegurar que a compilação de injeção de dependência e a camada de visualização respondessem com estabilidade:

### Passo 1: Registro e Configuração do Módulo (`registration.php` e `module.xml`)

* O arquivo `src/app/code/Webjump/Jhonatan/registration.php` foi criado invocando `ComponentRegistrar::register`, definindo a chave primária `Webjump_Jhonatan`.
* Em `src/app/code/Webjump/Jhonatan/etc/module.xml`, configurou-se a tag `<sequence>`, declarando a precedência dos módulos `Magento_Theme` e `Magento_Cms` para garantir que o mecanismo de páginas e temas esteja disponível antes da resolução do módulo.

### Passo 2: Modelagem e Camada de Dados (`ViewModel/HomeBlock.php`)

* Implementou-se a classe `HomeBlock` em conformidade com `Magento\Framework\View\Element\Block\ArgumentInterface`, viabilizando sua passagem como argumento de layout.
* Foram modelados métodos tipados com `declare(strict_types=1)` para fornecer os textos de cabeçalho e a matriz de diferenciais da loja (Logística Integrada, Garantia Homologada, Transação Criptografada e Suporte Especializado).
* Adicionou-se o método de guarda `hasBenefits()`, garantindo que se a coleção de itens estiver vazia, o template aborte a renderização de elementos estruturais órfãos no DOM.

### Passo 3: Injeção Estrutural via Layout XML (`cms_index_index.xml`)

* Mapeou-se a página inicial através do handle `view/frontend/layout/cms_index_index.xml`.
* No bloco `<head>`, declarou-se a inclusão da folha `Webjump_Jhonatan::css/home-block.css`.
* Sob o `<referenceContainer name="content">`, instanciou-se o bloco `webjump.jhonatan.home.block` utilizando a classe nativa genérica `Magento\Framework\View\Element\Template`, injetando a instância do ViewModel através do nó `<argument name="view_model" xsi:type="object">`.

### Passo 4: Apresentação Semântica e Sanitização (`home_block.phtml`)

* O arquivo de template foi estruturado utilizando marcação HTML semântica (`<section>`, `<header>`, `<ul role="list">`, `<li>`).
* Implementou-se checagem defensiva de tipo com `instanceof \Webjump\Jhonatan\ViewModel\HomeBlock`.
* Todos os nós de texto foram encapsulados por `$block->escapeHtml()` e as coordenadas vetoriais protegidas por `$block->escapeHtmlAttr()`, prevenindo vulnerabilidades de injeção de script via parâmetros.

### Passo 5: Estilização Dedicada e Pipeline de Build

* O arquivo `home-block.css` foi estruturado sob paleta corporativa neutra (*slate/zinc*), com grid adaptável via `auto-fit` e regras de media query para mobile.
* No terminal do WSL2, executou-se o ciclo integral de compilação da plataforma (`setup:upgrade`, `setup:di:compile`, `setup:static-content:deploy -f` e `cache:flush`) para gerar proxies na pasta `generated/code/` e publicar os assets estáticos em `pub/static/`.

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### 1. Separação Arquitetural: ViewModel vs Bloco Legado (`Block`)

* **Cenário:** O Magento tradicionalmente concentrava métodos de busca e formatação em classes filhas de `Magento\Framework\View\Element\Template`.
* **Decisão de Engenharia:** Adotou-se o padrão **ViewModel** via `ArgumentInterface`. Blocos clássicos geram herança desnecessária e acoplamento pesado com o framework. O ViewModel atua como um Plain Old PHP Object (POPO) focado puramente em dados, simplificando testes unitários e permitindo reutilização entre múltiplos templates sem poluir o ciclo de vida do bloco.

### 2. Tratamento Defensivo contra Containers Vazios e XSS

* **Cenário:** No ecossistema PHP/Magento, a impressão direta de variáveis na camada visual (`echo $var`) não aplica sanitização automática, criando brechas de segurança de Cross-Site Scripting (XSS). Além disso, coleções nulas renderizariam bordas e cabeçalhos em branco na vitrine.
* **Solução Adotada:** Implementação da condicional `$viewModel->hasBenefits()` na raiz do template e aplicação mandatória de `$block->escapeHtml()` e `$block->escapeHtmlAttr()` em todos os atributos e nós dinâmicos.

### 3. Vetores SVG Inline Calibrados vs Emojis/Ícones Não-Semânticos

* **Cenário:** Prototipações iniciais com caracteres de emojis ou paths vetoriais desenhados fora de proporção causavam distorções de renderização nos cards e quebravam a estética de um portal enterprise.
* **Solução Adotada:** Mapeamento de paths vetoriais estritamente projetados para o grid `viewBox="0 0 24 24"`, com espessura de traço uniforme (`stroke-width="1.75"`), atributo `aria-hidden="true"` para leitores de tela e centralização em container quadrado de 44x44px.

### 4. Resolução de Diretórios WSL2 vs Compact Folders do VS Code

* **Cenário:** O agrupamento visual de pastas unifilhas no VS Code (`code\Webjump\Jhonatan`) poderia induzir à criação de nomes com caracteres literais de contra-barra no Linux.
* **Solução Adotada:** Auditoria direta via terminal Ubuntu com `ls -la src/app/code/Webjump/Jhonatan`, comprovando a existência de subpastas reais no filesystem ext4 e assegurando que o autoloader da PSR-4 resolva os namespaces sem falhas.

---

## 6. A Jornada de um Request no Magento 2 (Com Minhas Palavras)

Entender como uma requisição vira HTML no Magento é o ponto central que conecta essa plataforma com o que foi explorado em AEM e Shopify:

```text
   Navegador faz a requisição (GET /)
                  │
                  ▼
          [ Front Controller ]
   Identifica a rota e aciona a Action da Home
                  │
                  ▼
         [ Layout Processing ]
   Lê o arquivo cms_index_index.xml e descobre
   quais blocos e CSS pertencem àquela página
                  │
                  ▼
        [ Injeção do ViewModel ]
   O Magento resolve a dependência e entrega a
   classe HomeBlock instanciada para o Bloco
                  │
                  ▼
          [ Template .phtml ]
   O template consome os métodos do ViewModel,
   escapa as strings e monta a marcação HTML
                  │
                  ▼
          Resposta HTTP final

```

### O Paralelo com Outras Plataformas:

* **No AEM:** A URL era resolvida pelo Sling, que encontrava o nó do JCR; o nó apontava para um `sling:resourceType`, o Sling Model injetava os dados do Java e o arquivo HTL renderizava a tela.
* **No Shopify:** A requisição encontrava um template JSON, que carregava as *sections* modulares e preenchia o HTML utilizando as tags e filtros do Liquid.
* **No Magento:** O processo é idêntico em arquitetura. O **Layout XML** cumpre o papel da árvore do Sling/JSON estruturando as áreas; o **ViewModel** faz o papel exato do **Sling Model** isolando a lógica do PHP; e o template **`.phtml`** é o nosso HTL/Liquid que apenas desenha o que recebe.

---

## 7. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as evidências formais de homologação do Desafio 13.1, auditando o registro do módulo na CLI, a integridade da folha de estilos via rede e a renderização semântica do componente na vitrine:

### Evidência 01 — Módulo Registrado e Ativo na CLI
* **Análise Visual/Técnica:** Retorno limpo do terminal do Ubuntu (WSL2) executando `bin/magento module:status Webjump_Jhonatan`, comprovando que o componente foi identificado pelo `registration.php` e carregado com status `Module is enabled`.

<img width="100%" alt="01-module-status-cli" src="https://github.com/user-attachments/assets/777b289f-7c7c-41ef-8a78-975a18edd6ad" />

---

### Evidência 02 — Seção de Diferenciais Renderizada na Home Pública
* **Análise Visual/Técnica:** Vitrine da loja acessada via HTTPS em `https://magento.test`, evidenciando a renderização do bloco institucional no container principal com título, subtítulo e os 4 cards devidamente alinhados.

<img width="100%" alt="02-home-block-rendered" src="https://github.com/user-attachments/assets/0d25d306-2a78-424f-b9ed-c8079710a53a" />

---

### Evidência 03 — Estrutura Semântica e Acessibilidade no DOM
* **Análise Visual/Técnica:** Inspeção de elementos no DevTools (Elements) comprovando o uso de tags semânticas `<section>`, `<header>`, `<ul>` e `<svg>`, com a marcação limpa, hierarquia correta e conformidade sem tags vazias.

<img width="100%" alt="03-dom-semantic-elements" src="https://github.com/user-attachments/assets/62484152-18f5-42a5-a32c-c48590b0b706" />

---

### Evidência 04 — Isolamento e Carregamento do CSS Dedicado
* **Análise Visual/Técnica:** Painel de Network do DevTools filtrado por CSS, demonstrando a requisição do arquivo `home-block.css` atendida com status HTTP `200 OK`, validando a injeção declarativa via `<head>` do layout XML.

<img width="100%" alt="04-network-css-200" src="https://github.com/user-attachments/assets/35d43041-872f-4284-ae17-079df05ec44b" />
