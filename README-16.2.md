# Implementação Técnica Magento 2: Estrutura, Textos e E-mail no Tema Frontend "Noite Assombrada"

## 1. Visão Geral do Entregável

Este repositório/PR formaliza a entrega do **Desafio 16.2 (Estrutura, textos e e-mail)** da Sprint 8. O objetivo principal consistiu em estender o tema **"Noite Assombrada"** (`Webjump/noite-assombrada`) para além da camada visual, atuando em três frentes estruturais complementares: organização do layout via XML, vocabulário temático via i18n e customização do e-mail transacional de novo pedido.

O projeto foi construído respeitando integralmente as convenções de desenvolvimento de frontend do Magento 2 (mesclagem de layout XML, sobrescrita cirúrgica de templates `.phtml`, internacionalização via CSV e estilização de e-mail via `_email-extend.less`), sem o uso de bibliotecas de terceiros ou modificações nos arquivos originais da plataforma:

1. **Mesclagem de Layout XML (`default.xml`):** Aplicação declarativa de três instruções no tema filho — injeção de uma faixa de campanha global no container `page.top`, movimentação da busca global para o `header.panel` e remoção do bloco lateral `catalog.compare.sidebar` — sem sobrescrever nenhum arquivo do tema pai (`Magento/luma`) ou do core (`vendor/magento`).
2. **Sobrescrita Cirúrgica de Template `.phtml`:** Cópia integral do template `logo.phtml` original do módulo `Magento_Theme` para o tema filho, preservando 100% da estrutura e da acessibilidade do core, com o acréscimo exclusivo de um badge temático acoplado à marca.
3. **Internacionalização e Vocabulário de Campanha (`i18n/pt_BR.csv`):** Criação de dois dicionários de tradução — um no tema (aplicado à vitrine) e um no módulo (aplicado ao corpo do e-mail transacional) — com vocabulário temático de Halloween aplicado a mais de 10 termos nativos da loja.
4. **Customização do E-mail Transacional de Novo Pedido:** Sobrescrita do template `order_new.html` do `Magento_Sales`, estilização completa via `_email-extend.less` com a paleta do tema e registro oficial do template via `email_templates.xml` no módulo, garantindo a substituição nativa pelo framework de e-mail do Magento.
5. **Governança de Configuração de E-mail:** Correção e ajuste dos configs `sales_email/order/template`, `sales_email/order/guest_template` e `sales_email/order/identity`, cobrindo tanto pedidos de clientes autenticados quanto pedidos de convidados (guest checkout).

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo está detalhada a estrutura de diretórios do tema (`app/design/frontend/Webjump/noite-assombrada`) e do módulo (`app/code/Webjump/Jhonatan`). As imagens comprobatórias de QA foram hospedadas diretamente no CDN do GitHub (via upload em Issues do repositório), garantindo máxima fidelidade visual sem inflar o histórico de commits do projeto:

```text
src/app/design/frontend/Webjump/noite-assombrada/
├── i18n/
│   ├── pt_BR.csv                                           # Dicionário pt_BR da vitrine (10+ termos)
│   └── en_US.csv                                           # Espelho em inglês
├── Magento_Sales/
│   └── email/
│       └── order_new.html                                  # Template sobrescrito (conteúdo de campanha)
├── Magento_Theme/
│   ├── layout/
│   │   └── default.xml                                     # Move busca, remove compare, injeta faixa
│   └── templates/
│       └── html/
│           ├── faixa-halloween.phtml                       # Faixa de campanha injetada no topo
│           └── header/
│               └── logo.phtml                              # Logo sobrescrito com badge temático
└── web/css/source/
    ├── _extend.less                                        # Banner, checkout escuro, footer 100%
    └── _email-extend.less                                  # Identidade visual do e-mail transacional

src/app/code/Webjump/Jhonatan/
├── etc/
│   └── email_templates.xml                                 # Registro oficial do template no módulo
├── i18n/
│   └── pt_BR.csv                                           # Tradução do corpo do e-mail
└── view/frontend/
    └── email/
        └── order_new.html                                  # Arquivo referenciado pelo email_templates.xml
```

---

## 3. Rastreabilidade dos Critérios de Aceite

A tabela a seguir correlaciona os requisitos técnicos exigidos no Desafio 16.2 com as implementações realizadas e as respectivas evidências de validação:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidência de QA |
| --- | --- | --- | --- |
| **Faixa aparece em todas as páginas via Layout XML** | Injeção do bloco `halloween.faixa` no container `page.top` via `referenceContainer` no `default.xml`. | **Concluído** | [Evidência 03](https://github.com/user-attachments/assets/0efe09cf-e22f-4441-8e76-b8298267dc9c), [Evidência 04](https://github.com/user-attachments/assets/5f99a65e-a6a0-4011-bf6b-d28cc7370885) e [Evidência 05](https://github.com/user-attachments/assets/313e2f7b-4b32-4a07-850d-074582da575f) |
| **Um bloco removido e outro movido pelo layout** | `<referenceBlock name="catalog.compare.sidebar" remove="true"/>` + `<move element="top.search" destination="header.panel" after="-"/>`. | **Concluído** | [Evidência 04](https://github.com/user-attachments/assets/5f99a65e-a6a0-4011-bf6b-d28cc7370885) e [Evidência 06](https://github.com/user-attachments/assets/addb4440-2b3a-4fc6-9ca3-6eea50b3c855) |
| **Template sobrescrito no caminho correto** | Cópia integral do `logo.phtml` do `Magento_Theme` com acréscimo do badge temático. | **Concluído** | [Evidência 06](https://github.com/user-attachments/assets/addb4440-2b3a-4fc6-9ca3-6eea50b3c855) e [Evidência 09](https://github.com/user-attachments/assets/e360173a-2332-46eb-8c87-9dd042332f93) |
| **CSV de tradução com ao menos 6 termos** | Dicionário `i18n/pt_BR.csv` no tema com 10 termos temáticos aplicados. | **Concluído** | [Evidência 03](https://github.com/user-attachments/assets/0efe09cf-e22f-4441-8e76-b8298267dc9c), [Evidência 04](https://github.com/user-attachments/assets/5f99a65e-a6a0-4011-bf6b-d28cc7370885), [Evidência 05](https://github.com/user-attachments/assets/313e2f7b-4b32-4a07-850d-074582da575f) e [Evidência 06](https://github.com/user-attachments/assets/addb4440-2b3a-4fc6-9ca3-6eea50b3c855) |
| **E-mail de novo pedido com identidade da campanha** | Sobrescrita do `order_new.html`, estilização via `_email-extend.less` e registro via `email_templates.xml`. | **Concluído** | [Evidência 08](https://github.com/user-attachments/assets/f974fc36-b198-4929-a2a5-1a9d3a2e881f) |
| **Saída escapada e dentro de `__()`** | Todos os textos passam por `__()` ou `{{trans}}`, com `escapeHtml`/`escapeHtmlAttr`/`escapeUrl`. | **Concluído** | [Evidência 09](https://github.com/user-attachments/assets/e360173a-2332-46eb-8c87-9dd042332f93) |
| **Auditoria CLI e Integridade Git** | Execução bem-sucedida do `setup:di:compile` e saída limpa no `git status` sem alterações no diretório `vendor/magento`. | **Concluído** | [Evidência 10](https://github.com/user-attachments/assets/0af11795-ff19-4dda-be6a-3a096a6b69c5) |

---

## 4. Passo a Passo do Processo de Desenvolvimento

### Passo 1: Mesclagem de Layout XML (`default.xml`)

- Criação do arquivo `Magento_Theme/layout/default.xml` no tema filho com três instruções de mesclagem declarativa:
  - `<move element="top.search" destination="header.panel" after="-"/>` — busca global movida para o painel superior.
  - `<referenceBlock name="catalog.compare.sidebar" remove="true"/>` — bloco de comparação removido.
  - `<referenceContainer name="page.top">` com bloco `halloween.faixa` — faixa de campanha injetada no topo de todas as páginas.

### Passo 2: Template da Faixa e Override do Logo

- Criação de `faixa-halloween.phtml` com os textos escapados via `$block->escapeHtml()` e URLs via `$block->escapeUrl()`, todos passando por `__()` para tradução.
- Cópia integral do `logo.phtml` original do `Magento_Theme` para o tema filho, adicionando apenas o `<span class="halloween-logo-badge">` dentro da tag `<a class="logo">` para não colidir com os links utilitários do painel superior.

### Passo 3: Internacionalização (i18n)

- Criação do dicionário `i18n/pt_BR.csv` no tema com 10 termos temáticos:

```csv
"Add to Cart","Colocar no caldeirão"
"Shopping Cart","Caldeirão"
"My Wish List","Meus Feitiços"
"Search entire store here...","Procure algo assombroso..."
"Sign In","Entrar no covil"
"Create an Account","Criar pacto"
"Compare Products","Comparar maldições"
"IN STOCK","Disponível na tumba"
"In stock","Disponível na tumba"
"Default welcome msg!","Boas-vindas à Noite Assombrada!"
```

- Criação de `i18n/pt_BR.csv` no módulo, cobrindo as chaves específicas do corpo do e-mail transacional.

### Passo 4: Customização do E-mail Transacional

- Cópia do `order_new.html` do `Magento_Sales` para o tema, preservando 100% das diretivas nativas (`{{template config_path}}`, `{{layout handle}}`, `{{trans}}`, `{{depend}}`).
- Criação de `web/css/source/_email-extend.less` com a paleta de Halloween aplicada ao e-mail (fundo roxo noite, borda laranja abóbora, frase de campanha em verde bruxa).
- Criação do arquivo `etc/email_templates.xml` no módulo registrando o template customizado junto ao `Magento_Email`.

### Passo 5: Governança de Configuração de E-mail

- Ajuste de `sales_email/order/template` e `sales_email/order/guest_template` apontando para o template customizado.
- Correção de `sales_email/order/identity` para `general`.
- Configuração de `trans_email/ident_general/email` e `trans_email/ident_general/name`.

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### Problema 1 — E-mail caindo no template do core

Durante os testes iniciais, o e-mail chegava com o conteúdo em inglês do core, apesar do arquivo `order_new.html` customizado existir no tema.

- **Análise e Causa Raiz:** O Magento 2 prioriza templates registrados em `email_templates.xml` (declaração em módulo) sobre arquivos soltos em temas. O método `loadDefault()` do `Magento\Email\Model\Template` consulta apenas os templates declarados em XML de módulo, ignorando completamente arquivos presentes em `app/design/frontend/.../Magento_Sales/email/`.
- **Solução:** Criação do arquivo `etc/email_templates.xml` no módulo `Webjump_Jhonatan`, registrando o template customizado com `id`, `label`, `file` e `module`, e movendo a cópia do arquivo para `view/frontend/email/order_new.html` dentro da estrutura do módulo.

### Problema 2 — Envio de e-mail falhando silenciosamente

O método `$orderSender->send($order)` executava sem exception, mas o Mailcatcher não recebia e-mail. O log `system.log` mostrava `main.ERROR: Invalid sender data`.

- **Análise e Causa Raiz:** O config `sales_email/order/identity` estava apontando para o próprio código do template (`sales_email_order_template`) em vez do identificador da identidade de envio (`general`). Como não havia `trans_email/ident_sales_email_order_template/email` cadastrado, o Magento bloqueava o envio.
- **Solução:** Correção via `bin/magento config:set sales_email/order/identity general` e preenchimento de `trans_email/ident_general/email` e `trans_email/ident_general/name`.

### Problema 3 — Pedidos de convidado usando template diferente

Após corrigir o template do cliente logado, o e-mail ainda chegava em inglês quando o pedido era realizado como convidado.

- **Análise e Causa Raiz:** Pedidos realizados por convidados (guest checkout) utilizam um config separado — `sales_email/order/guest_template` — em vez de `sales_email/order/template`. O método `prepareTemplate()` do `OrderSender` bifurca o comportamento com base em `$order->getCustomerIsGuest()`.
- **Solução:** Configuração de `sales_email/order/guest_template` também apontando para `webjump_halloween_order_template`, cobrindo ambos os fluxos.

---

## 6. Estrutura das Regras de Layout XML e LESS do Tema

Abaixo estão os trechos principais da implementação desenvolvida no tema para o 16.2:

### Layout XML (`Magento_Theme/layout/default.xml`)

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <!-- 1. Mover a busca global para o painel do topo -->
        <move element="top.search" destination="header.panel" after="-"/>

        <!-- 2. Remover a barra de comparação lateral que polui a navegação -->
        <referenceBlock name="catalog.compare.sidebar" remove="true"/>

        <!-- 3. Injetar a faixa de campanha no topo de todas as páginas -->
        <referenceContainer name="page.top">
            <block class="Magento\Framework\View\Element\Template"
                   name="halloween.faixa"
                   before="-"
                   template="Magento_Theme::html/faixa-halloween.phtml"/>
        </referenceContainer>
    </body>
</page>
```

### Registro do Template de E-mail (`etc/email_templates.xml`)

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Email:etc/email_templates.xsd">
    <template id="webjump_halloween_order_template"
              label="Confirmacao do Pedido - Noite Assombrada"
              file="order_new.html"
              type="html"
              module="Webjump_Jhonatan"
              area="frontend"/>
</config>
```

### Estilização do E-mail (`_email-extend.less`)

```less
// Paleta de Cores do Tema Noite Assombrada
@color-abobora:    #FF6B1A;
@color-roxo-noite: #2A1B3D;
@color-verde-bruxa:#7CFF6B;
@color-cinza-neblina:#B8B8C4;
@color-branco-gelo:#EDE7F6;

body, .body, html {
    background-color: @color-roxo-noite !important;
    color: @color-branco-gelo !important;
}

.greeting, h1 {
    color: @color-abobora !important;
    font-size: 24px;
}

.halloween-email-intro {
    color: @color-verde-bruxa !important;
    font-size: 16px;
    font-weight: bold;
}
```

---

## 7. Decisões de Engenharia Frontend: Conformidade e Performance

- **Por que mesclar o layout em vez de sobrescrever o arquivo inteiro do core?**
A mesclagem declarativa preserva o recebimento automático de patches de segurança e atualizações do tema pai (`Magento/luma`). A sobrescrita de um arquivo de layout inteiro congelaria a versão atual e exigiria conferência manual a cada upgrade do Magento.

- **Por que registrar o template de e-mail via `email_templates.xml` em vez de banco de dados?**
Este é o mecanismo oficial do Adobe Commerce para declarar templates de e-mail customizados. Ele integra o template ao core do `Magento_Email`, tornando-o detectável pelo método `loadDefault()` e garantindo que o arquivo seja versionado via Git — diferente de templates no banco, que não são versionáveis e ficam invisíveis ao code review.

- **Por que separar o dicionário de tradução em dois CSVs (tema + módulo)?**
O dicionário do tema atua na vitrine (área `frontend`). O dicionário do módulo cobre a área de e-mail, que é renderizada em contexto isolado pela área `frontend` mas com escopo próprio do `Magento_Email`. Sem a separação, as chaves do corpo do e-mail não seriam traduzidas pela cascata de i18n do tema.

---

## 8. Relatório Detalhado de Quality Assurance (QA)

As 10 evidências a seguir comprovam a validação funcional da estrutura de layout, das traduções e do e-mail transacional, além da integridade técnica da entrega. Os links utilizam as imagens hospedadas no CDN de Issues do GitHub:

### Evidência 01 — Registro no Sistema: Grade de Temas do Magento Admin

- **Análise Visual:** Grade oficial do painel administrativo em **Content → Design → Themes** (`system_design_theme/index`) listando os 3 registros do ambiente e confirmando a cadeia de herança (`Magento Blank` → `Magento Luma` → `Noite Assombrada`), com o `theme_path` = `Webjump/noite-assombrada`.

<img width="1915" height="1026" alt="Evidência 01" src="https://github.com/user-attachments/assets/5103d26d-7e94-4bf0-9192-38ca6ee94786" />

---

### Evidência 02 — Ativação de Tema: Design Configuration na Store View

- **Análise Visual:** Configuração de design no painel administrativo em **Content → Design → Configuration**, demonstrando o tema "Noite Assombrada" ativo no campo *Applied Theme* sob o escopo *Default Store View*.

<img width="1917" height="1026" alt="Evidência 02" src="https://github.com/user-attachments/assets/72b3baf2-b518-4568-afa9-8a28f68091f5" />

---

### Evidência 03 — Faixa de Campanha na Home Page

- **Análise Visual:** Home Page (`https://magento.test`) exibindo a faixa "NOITE ASSOMBRADA" injetada via `default.xml` no topo absoluto da página, o título "HOME PAGE" com a tipografia temática Creepster e o bloco institucional "Nossos Compromissos" preservado (retrocompatibilidade com Sprint 6). O cabeçalho também demonstra as traduções do i18n aplicadas ("Entrar no covil", "Criar pacto", "Boas-vindas à Noite Assombrada").

<img width="1915" height="1027" alt="Evidência 03" src="https://github.com/user-attachments/assets/0efe09cf-e22f-4441-8e76-b8298267dc9c" />

---

### Evidência 04 — Faixa Persistente na Listagem de Produtos (PLP)

- **Análise Visual:** Página de categoria (`https://magento.test/perifericos.html`) comprovando que a faixa foi injetada pelo `default.xml` (aparece em rota distinta da home), com o título "PERIFÉRICOS" em Creepster, barra lateral sem o bloco "Compare Products" (removido via layout XML) e o botão "Colocar no caldeirão" renderizado no card do produto.

<img width="1915" height="1026" alt="Evidência 04" src="https://github.com/user-attachments/assets/5f99a65e-a6a0-4011-bf6b-d28cc7370885" />

---

### Evidência 05 — Identidade Visual na Página de Detalhes do Produto (PDP)

- **Análise Visual:** Página individual do produto (`https://magento.test/mouse-gamer-pro-wireless.html`) com URL visível, galeria integrada ao fundo escuro, título em Creepster, badge verde "Produto Sustentável" (reaproveitado da Sprint 7 / Desafio 14.1), preço destacado em laranja, botão "Colocar no caldeirão" e indicador de estoque com a tradução temática "DISPONÍVEL NA TUMBA".

<img width="1917" height="1027" alt="Evidência 05" src="https://github.com/user-attachments/assets/313e2f7b-4b32-4a07-850d-074582da575f" />

---

### Evidência 06 — Cabeçalho com Logo Sobrescrito e Busca Movida

- **Análise Visual:** Enquadramento do topo da PDP demonstrando: o logo Luma original preservado com o badge "NOITE ASSOMBRADA" acoplado à marca (comprovando a sobrescrita cirúrgica do `logo.phtml`); a busca global reposicionada no painel superior (`header.panel`) ao lado dos links utilitários (comprovando o `<move>` do layout XML); e as traduções do i18n ativas nos links "Entrar no covil", "Criar pacto" e no placeholder "Procure algo assombroso...".

<img width="1915" height="392" alt="Evidência 06" src="https://github.com/user-attachments/assets/addb4440-2b3a-4fc6-9ca3-6eea50b3c855" />

---

### Evidência 07 — Rodapé da Loja com Newsletter e Delimitador Temático

- **Análise Visual:** Seção inferior da loja destacando a linha horizontal laranja abóbora demarcando o início do footer, o formulário de cadastro de newsletter com botão "Subscribe" na cor de destaque, os links institucionais e a barra de direitos autorais. O rodapé ocupa 100% da largura útil sem overflow lateral.

<img width="1912" height="737" alt="Evidência 07" src="https://github.com/user-attachments/assets/6aef4fad-93f6-4107-905c-9fba6f4144d5" />

---

### Evidência 08 — E-mail Transacional de Novo Pedido no Mailcatcher

- **Análise Visual:** E-mail de novo pedido capturado no Mailcatcher (`localhost:1080`, aba HTML), demonstrando: o assunto em português ("Confirmação do seu pedido assombroso na Main Website Store"), a saudação personalizada em laranja abóbora, a frase temática "Seu pedido assombroso foi confirmado e colocado no caldeirão!" em verde bruxa, o corpo integralmente traduzido (Informações de Cobrança, Forma de Pagamento, Itens, Qtd, Preço) e o fundo roxo noite com borda laranja aplicados via `_email-extend.less`.

<img width="1916" height="1027" alt="Evidência 08" src="https://github.com/user-attachments/assets/f974fc36-b198-4929-a2a5-1a9d3a2e881f" />

---

### Evidência 09 — Arquitetura de Código: Estrutura do Tema no VS Code

- **Análise Visual:** Hierarquia de diretórios em `src/app/design/frontend/Webjump/noite-assombrada` com as pastas `i18n`, `Magento_Sales/email`, `Magento_Theme/layout`, `Magento_Theme/templates/html/header` e `web/css/source` organizadas. O arquivo `theme.xml` aberto ao lado confirma a herança direta do `Magento/luma` (`<parent>Magento/luma</parent>`), e as abas abertas evidenciam os arquivos do 16.2 (`pt_BR.csv`, `en_US.csv`, `default.xml`, `faixa-halloween.phtml`, `logo.phtml`, `order_new.html`, `_email-extend.less`).

<img width="1917" height="1077" alt="Evidência 09" src="https://github.com/user-attachments/assets/e360173a-2332-46eb-8c87-9dd042332f93" />

---

### Evidência 10 — Auditoria CLI e Git: Compilação de Injeção de Dependências

- **Análise Visual:** Terminal do WSL2 validando o sucesso da compilação com `bin/magento setup:di:compile` (100% em 32 segundos) e o comando `git status -uall` atestando que todas as modificações estão restritas a `src/app/code/Webjump/Jhonatan/` e `src/app/design/frontend/Webjump/noite-assombrada/`, comprovando que os arquivos do core do Magento (`vendor/magento`) e do tema pai (`Magento/luma`) permaneceram intocados.

<img width="1000" height="537" alt="Evidência 10" src="https://github.com/user-attachments/assets/0af11795-ff19-4dda-be6a-3a096a6b69c5" />
```