# Implementação Técnica Magento 2: Seção Própria de Customer Data

## 1. Visão Geral do Entregável

Este repositório e Pull Request formalizam a entrega técnica do Desafio 17.6 (Seção própria de customer-data) da Sprint 8 (Magento 2 / Adobe Commerce). O objetivo principal consistiu em projetar, desenvolver e homologar uma seção personalizada de dados privados do cliente (customer-data), garantindo o gerenciamento dinâmico de estado de sessão sem invalidar o cache público (Full Page Cache / Varnish) e sem nenhum recarregamento de página.

A implementação atende aos seguintes pilares de engenharia de software corporativa:

- **Gerenciamento Real de Sessão por Visitante:** O contador de sustos é persistido diretamente na sessão nativa do visitante através de `\Magento\Customer\Model\Session`, assegurando que cada cliente mantenha seu histórico de forma estritamente isolada.
- **Invalidação Declarativa de Cache (sections.xml):** Associação formal das ações mutadoras à seção `halloween-sustos`. Tanto a adição de produtos ao carrinho (`checkout/cart/add`) quanto a interação com o Easter Egg (`jhonatan/susto/assustar`) invalidam a seção e disparam o carregamento assíncrono via `customer-data.js`.
- **Inviolabilidade do Full Page Cache (FPC):** Zero dados de sessão são impressos no HTML pelo PHP. O documento público entregue pelo servidor é 100% estático e neutro; a hidratação visual ocorre exclusivamente no cliente via Knockout.js.
- **Gamificação com Animação Desacoplada:** Integração de um elemento flutuante na tela com aparições espaçadas e uma animação do fantasma com lamparina em CSS (`mix-blend-mode: screen`), fornecendo feedback visual rico à invalidação de sessão.

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore completa de arquivos desenvolvidos e estendidos no módulo (`src/app/code/Webjump/Jhonatan/`) e no tema (`src/app/design/frontend/Webjump/noite-assombrada/`):

```text
src/
├── app/
│   ├── code/
│   │   └── Webjump/
│   │       └── Jhonatan/
│   │           ├── Controller/
│   │           │   └── Susto/
│   │           │       └── Assustar.php                    # Action POST que incrementa a sessão
│   │           ├── CustomerData/
│   │           │   └── Sustos.php                          # Provedor da seção (SectionSourceInterface)
│   │           ├── Observer/
│   │           │   └── CartAddSustoObserver.php            # Incrementa o susto ao adicionar ao carrinho
│   │           ├── etc/
│   │           │   └── frontend/
│   │           │       ├── di.xml                          # Registro em SectionPoolInterface
│   │           │       ├── events.xml                      # Observer checkout_cart_add_product_complete
│   │           │       ├── routes.xml                      # Registro da rota jhonatan
│   │           │       └── sections.xml                    # Regras de invalidação da seção
│   │           └── view/
│   │               └── frontend/
│   │                   ├── layout/
│   │                   │   └── default.xml                 # Injeção do bloco no header.panel
│   │                   ├── templates/
│   │                   │   └── sustos.phtml                # Inicialização declarativa (x-magento-init)
│   │                   └── web/
│   │                       ├── images/
│   │                       │   └── fantasma-susto.png      # Imagem do fantasma com a lamparina
│   │                       ├── js/
│   │                       │   └── sustos.js               # Componente Knockout e gestão de customerData
│   │                       └── template/
│   │                           └── sustos.html             # Template reativo (badge + animação)
│   │
│   └── design/
│       └── frontend/
│           └── Webjump/
│               └── noite-assombrada/
│                   ├── i18n/
│                   │   ├── en_US.csv                       # Dicionário de tradução da campanha (en_US)
│                   │   └── pt_BR.csv                       # Dicionário de tradução da campanha (pt_BR)
│                   └── web/
│                       └── css/
│                           └── source/
│                               ├── _extend.less            # Importação modular do componente
│                               └── components/
│                                   └── _sustos.less        # Estilos da badge, Easter Egg e voo do fantasma
│
README-17.6.md                                              # Documentação técnica e matriz de homologação
```

## 3. Rastreabilidade dos Critérios de Aceite

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
| --- | --- | --- | --- |
| A seção é lida pelo componente e exibida na tela | Classe `Sustos.php` associada a `SectionPoolInterface`, consumida por `customerData.get('halloween-sustos')` e renderizada via Knockout. | Concluído | Evidências 02 e 06 |
| O valor é individual por visitante | Controle de estado via `\Magento\Customer\Model\Session`. Sessões limpas em navegadores distintos iniciam do zero de forma independente. | Concluído | Evidência 04 |
| Ao executar a ação declarada, o valor se atualiza sem recarregar a página | Mapeamento em `sections.xml`. Ações disparam a requisição assíncrona `/customer/section/load/`, atualizando a interface em tempo real. | Concluído | Evidências 02 e 03 |
| Nenhum dado individual foi impresso no HTML pelo PHP | Template `sustos.phtml` sem variáveis de sessão. Proteção total do Full Page Cache comprovada via código-fonte do navegador. | Concluído | Evidência 05 |
| Configuração de `sections.xml` e injeção em `di.xml` | Declaração da seção em `etc/frontend/sections.xml` e registro no `sectionSourceMap` de `SectionPoolInterface` em `etc/frontend/di.xml`. | Concluído | Evidência 07 |
| Inviolabilidade do núcleo e compilação limpa | Execução bem-sucedida de `setup:di:compile` em 100% e verificação de `vendor/` 100% intocado via Git CLI. | Concluído | Evidência 08 |

## 4. Passo a Passo do Processo de Desenvolvimento

### Passo 1: Definição da Seção e Regras de Invalidação

Criou-se o arquivo `src/app/code/Webjump/Jhonatan/etc/frontend/sections.xml`, associando as ações `checkout/cart/add` e `jhonatan/susto/assustar` à seção `halloween-sustos`.

Em `src/app/code/Webjump/Jhonatan/etc/frontend/di.xml`, injetou-se a classe `Webjump\Jhonatan\CustomerData\Sustos` na coleção `sectionSourceMap` do serviço `Magento\Customer\CustomerData\SectionPoolInterface`.

### Passo 2: Provedor de Dados e Gestão de Sessão (Sustos.php)

Implementou-se a classe `Webjump\Jhonatan\CustomerData\Sustos` em conformidade com `Magento\Customer\CustomerData\SectionSourceInterface`.

O método `getSectionData()` lê a contagem real armazenada em `\Magento\Customer\Model\Session` (`sustos_count`) e retorna o array associativo com `total` e `mensagem`.

### Passo 3: Ações Mutadoras de Estado (Controller e Observer)

Controller Interativo (`Assustar.php`): Registrou-se a rota `jhonatan` em `etc/frontend/routes.xml`. Criou-se a action POST `Webjump\Jhonatan\Controller\Susto\Assustar`, que incrementa o valor na sessão do usuário e responde em formato JSON.

Observer de Catálogo (`CartAddSustoObserver.php`): Registrou-se o evento `checkout_cart_add_product_complete` em `etc/frontend/events.xml` para incrementar a sessão automaticamente sempre que um produto for adicionado ao carrinho.

### Passo 4: Componente Knockout e Template Reativo (sustos.js e sustos.html)

No arquivo `sustos.js`, inicializou-se a conexão `this.sustosData = customerData.get('halloween-sustos')` com propriedades computadas para manter reatividade sem acoplamento.

Criou-se um temporizador para posicionar um Easter Egg (fantasminha 👻) em coordenadas aleatórias com intervalos espaçados.

Ao interagir com o elemento, a action POST é enviada em segundo plano, disparando o voo do fantasma da esquerda para a direita durante 4,5 segundos.

O template `sustos.phtml` apenas inicializa o componente através de `<script type="text/x-magento-init">`, sem nenhum processamento de dados dinâmicos no servidor.

### Passo 5: Estilização Modular no Tema (_sustos.less)

Criou-se a folha modular `src/app/design/frontend/Webjump/noite-assombrada/web/css/source/components/_sustos.less`.

Implementou-se a animação `@keyframes voo-do-fantasma` com aceleração suave (`cubic-bezier`), utilizando `mix-blend-mode: screen` para eliminar o fundo escuro da imagem sobre a paleta da loja.

Importou-se o arquivo no `_extend.less` através de `@import 'components/_sustos.less';`.

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: Conflito de Animações e Oscilação Indesejada (Flickering)

**Sintoma:** O elemento flutuante apresentava tremores descoordenados na tela ao receber o cursor do mouse.

**Causa Raiz:** O mesmo nó HTML acumulava regras simultâneas de `@keyframes flutuar`, transformação de `:hover` e o binding customizado `tremor`, gerando conflitos na propriedade CSS `transform`.

**A Solução:** Separou-se a estrutura em dois contêineres distintos: a camada externa gerencia a posição fixa na janela, enquanto a camada interna gerencia a flutuação e a transição de escala de forma estável.

### O Problema: Disparo Duplo da Animação do Fantasma

**Sintoma:** Ao clicar no elemento interativo, o fantasma com a lamparina cruzava a tela duas vezes consecutivas.

**Causa Raiz:** O clique disparava a animação imediatamente por JavaScript e, frações de segundo depois, o retorno assíncrono do customer-data acionava a rotina de `subscribe()` do observable, disparando um segundo ciclo.

**A Solução:** Implementou-se uma trava booleana no componente (`if (self.animando()) return;`), ignorando chamadas concorrentes enquanto a travessia estiver em curso.

## 6. Decisões de Engenharia Frontend: Conteúdo Privado e Cache

### Por que utilizar customer-data em vez de chamadas AJAX manuais para carregar dados do usuário?

O mecanismo customer-data gerencia automaticamente a persistência em `localStorage` (`mage-cache-storage`), a expiração temporal e a invalidação atômica através do `sections.xml`. Utilizar chamadas AJAX ad-hoc exigiria gerenciar manualmente ciclos de vida, multiplicando requisições desnecessárias ao servidor e anulando os benefícios de cache local.

### Por que os dados do visitante não podem ser impressos no HTML pelo PHP?

O Magento opera com o Full Page Cache (FPC) ativado em ambientes de produção. Se uma página pública contiver dados de sessão processados no template pelo PHP, essa resposta é armazenada no cache público e entregue a todos os visitantes subsequentes, provocando vazamento de informação sensível. O customer-data assegura que o HTML público permaneça imutável, enquanto as informações privadas são preenchidas de forma estritamente individual no cliente.

## 7. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as 8 evidências formais comprovando a funcionalidade da seção, o isolamento por visitante, a proteção do FPC e a integridade de compilação:

### Evidência 01 — Demonstração do Voo do Fantasma / Easter Egg

**Análise Visual/Técnica:** Registro da listagem de catálogo com o elemento interativo flutuante (👻) posicionado na tela e o contador no cabeçalho em `Sustos: 0`.

![Evidência 01 — Demonstração do Voo do Fantasma / Easter Egg](https://github.com/user-attachments/assets/ab322166-2ca9-4d41-9d5f-6e92102b16e0)

### Evidência 02 — Contador Atualizado Sem Recarregar a Página

**Análise Visual/Técnica:** Página inicial com o contador incrementado para `Sustos: 2` sem reload. O painel Network comprova o POST em `/jhonatan/susto/assustar` (200 OK) sucedido pelo GET assíncrono em `/customer/section/load/?sections=halloween-sustos...` (200 OK).

![Evidência 02 — Contador Atualizado Sem Recarregar a Página](https://github.com/user-attachments/assets/429797ba-3293-4588-90e6-e0eacb33775d)

### Evidência 03 — Invalidação via Adição ao Carrinho

**Análise Visual/Técnica:** Página de produto registrando a adição de item ao carrinho, o sobrevoo do fantasma com lamparina em tela cheia e o novo incremento do contador para `Sustos: 4` acionado pela ação `checkout/cart/add`.

![Evidência 03 — Invalidação via Adição ao Carrinho](https://github.com/user-attachments/assets/b55eda28-93da-4a25-ad37-7f3d8ca5bdce)

### Evidência 04 — Isolamento de Sessão por Visitante (Duas Janelas)

**Análise Visual/Técnica:** Tela dividida comparando duas instâncias de navegação independentes. A Janela A (sessão ativa) apresenta `Sustos: 4`, enquanto a Janela B (sessão virgem) apresenta estritamente `Sustos: 0`, provando o isolamento individual.

![Evidência 04 — Isolamento de Sessão por Visitante (Duas Janelas)](https://github.com/user-attachments/assets/86e34346-6e39-41a2-8950-c27cb17911b1)

### Evidência 05 — Inviolabilidade do Full Page Cache (FPC / Código-Fonte)

**Análise Visual/Técnica:** Inspeção ao código-fonte bruto da página (`view-source`). Destaque para as linhas 131 a 145 demonstrando apenas o contêiner neutro e o descritor `text/x-magento-init`, atestando que nenhum dado privado foi impresso pelo PHP.

![Evidência 05 — Inviolabilidade do Full Page Cache (FPC / Código-Fonte)](https://github.com/user-attachments/assets/3f021c0e-267d-438b-aa82-b5a8f2ac66bb)

### Evidência 06 — Inspeção do Payload JSON da Seção Privada (Network)

**Análise Visual/Técnica:** Aba Network filtrada por `load`, exibindo a estrutura JSON expandida: `"halloween-sustos": {"total": 5, "mensagem": "Você já foi assustado 5 vezes nesta sessão!"}`, confirmando o consumo da API nativa.

![Evidência 06 — Inspeção do Payload JSON da Seção Privada (Network)](https://github.com/user-attachments/assets/3271c909-7f26-4859-a3bf-fbb9698249cb)

### Evidência 07 — Configuração de sections.xml e di.xml (CLI)

**Análise Visual/Técnica:** Saída de terminal com a leitura de `sections.xml` (ações `checkout/cart/add` e `jhonatan/susto/assustar` mapeadas) e `di.xml` (registro em `SectionPoolInterface`).

![Evidência 07 — Configuração de sections.xml e di.xml (CLI)](https://github.com/user-attachments/assets/ff621a29-a18f-452c-88c9-371749703bcf)

### Evidência 08 — Compilação Limpa e Core Intocado (CLI)

**Análise Visual/Técnica:** Terminal demonstrando compilação de DI concluída em 100% (`Generated code... successfully.`), branch ativa `exercicio/17.6-secao-customer-data` e confirmação formal de `vendor/` 100% intocado.

![Evidência 08 — Compilação Limpa e Core Intocado (CLI)](https://github.com/user-attachments/assets/7551add9-32f2-4aaf-9fdb-2717bfef401e)