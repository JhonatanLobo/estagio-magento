# Implementação Técnica Magento 2: Tema Frontend Personalizado "Noite Assombrada"

## 1. Visão Geral do Entregável

Este repositório/PR formaliza a entrega do **Desafio 16.1 (Criação de Tema Personalizado no Magento 2)**. O objetivo principal consistiu no desenvolvimento, configuração e homologação do tema frontend **"Noite Assombrada"** (`Webjump/noite-assombrada`), implementado com herança nativa do tema base **Magento Luma**.

O projeto foi construído respeitando integralmente as convenções de desenvolvimento de frontend do Magento 2 (Less, UI Components e injeção modular de CSS), sem o uso de bibliotecas de terceiros ou modificações nos arquivos originais da plataforma:

1. **Herança Limpa de Temas (`theme.xml`):** Aplicação estrita da cadeia de herança (`Magento Blank` $\rightarrow$ `Magento Luma` $\rightarrow$ `Webjump/noite-assombrada`), assegurando o reaproveitamento de componentes nativos e mantendo o diretório do core (`vendor/magento`) 100% intocado.
2. **Design System Temático e Paleta de Cores:** Customização visual completa por meio de variáveis Less (`@color-roxo-noite: #1a0f2b`, `@color-abobora: #ff6600` e derivações de contraste), garantindo uma identidade visual uniforme entre Home Page, catálogo (PLP) e página de detalhes de produto (PDP).
3. **Tipografia Local de Alta Performance:** Integração da fonte decorativa **Creepster** servida estritamente de forma local via `@font-face` nos formatos otimizados `.woff2` e `.ttf`, retornando código de status HTTP 200 OK sem disparar requisições externas para provedores de terceiros (Google Fonts).
4. **Layout Responsivo e Otimização Mobile-First:** Reestruturação dos cards de produto na listagem (PLP) com espaçamentos simétricos de 24 px no desktop e comportamento fluido em viewport mobile (393 px / padrão iPhone 16), com empilhamento vertical e botões *Add to Cart* preenchendo 100% da largura útil.
5. **Robustez de Deploy e Gestão de Evidências:** Resolução e sincronização dos metadados de visualização do tema (`preview_image`) no painel administrativo, compilação de injeção de dependências limpa (`setup:di:compile`) e hospedagem externa das evidências de QA via CDN do GitHub para manter o repositório leve.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo está detalhada a estrutura de diretórios do tema (`app/design/frontend/Webjump/noite-assombrada`). As imagens comprobatórias de QA foram hospedadas diretamente no CDN do GitHub (via upload em Issues do repositório), garantindo máxima fidelidade visual sem inflar o histórico de commits do projeto:

```text
src/app/design/frontend/Webjump/noite-assombrada/
├── etc/
│   └── view.xml                                            # Configurações de dimensões e proporções de imagens de catálogo
├── media/
│   └── preview.jpg                                         # Imagem de preview oficial exibida no Magento Admin
├── web/
│   ├── css/
│   │   └── source/
│   │       ├── _extend.less                                # Sobrescritas globais e extensões de componentes de UI
│   │       ├── _theme.less                                 # Declaração dos tokens de design (paleta de cores do tema)
│   │       └── _typography.less                            # Mapeamento da regra @font-face para a tipografia local
│   ├── fonts/
│   │   └── Creepster/
│   │       ├── creepster-regular.ttf                       # Arquivo TrueType da fonte Creepster
│   │       └── creepster-regular.woff2                     # Arquivo Web Open Font Format 2.0 de alta compressão
│   └── images/                                             # Ativos gráficos estáticos do tema
├── registration.php                                        # Registro do componente de tema no ecossistema Magento
└── theme.xml                                               # Declaração do título, media preview e herança do tema Luma

```

---

## 3. Rastreabilidade dos Critérios de Aceite

A tabela a seguir correlaciona os requisitos técnicos exigidos no Desafio 16.1 com as implementações realizadas e as respectivas evidências de validação:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidência de QA |
| --- | --- | --- | --- |
| **Herança Nativa de Tema** | Declaração da tag `<parent>Magento/luma</parent>` no `theme.xml` e registro via `registration.php` sem alterar o core. | **Concluído** | [Evidência 01](https://github.com/user-attachments/assets/c6d55a6d-9d56-4d51-aaeb-eab8e23978ea) e [Evidência 02](https://github.com/user-attachments/assets/726b4cdc-aba7-46ae-9f88-8ef71a249536) |
| **Ativação no Escopo da Loja** | Aplicação do tema "Noite Assombrada" no campo *Applied Theme* sob o escopo *Default Store View*. | **Concluído** | [Evidência 03](https://github.com/user-attachments/assets/52741d88-86cd-4472-8b1b-f4a2f4ac6985) |
| **Tipografia Local (Creepster)** | Carregamento dos binários locais `.woff2` e `.ttf` via `@font-face` sem requisições externas para CDN externa. | **Concluído** | [Evidência 04](https://github.com/user-attachments/assets/10978418-b69c-49fa-863f-2490474511fc) |
| **Customização de Catálogo (PLP Desktop)** | Estilização via `_extend.less` com fundo roxo escuro, títulos em fonte temática e cards com padding simétrico de 24 px sem distorcer imagens. | **Concluído** | [Evidência 05](https://github.com/user-attachments/assets/075b77ec-b760-4383-8b35-5b8f068afff9) |
| **Responsividade Mobile-First** | Reorganização de layout para tela mobile (393 px), empilhamento vertical e botão *Add to Cart* com largura de 100%. | **Concluído** | [Evidência 06](https://github.com/user-attachments/assets/4ca64b07-b6ed-4078-ae9b-15257c5b1241) |
| **Consistência na Home Page** | Propagação do tema escuro na página inicial, mantendo preservados os blocos CMS e widgets legados criados em sprints anteriores. | **Concluído** | [Evidência 07](https://github.com/user-attachments/assets/9dbd56fe-56c4-4a27-be5a-4dcb643605bc) |
| **Página de Detalhes do Produto (PDP)** | Aplicação das cores do tema na galeria, destaque do preço em laranja, botão estilizado e exibição dos atributos EAV do catálogo. | **Concluído** | [Evidência 08](https://github.com/user-attachments/assets/b99d88d0-9212-47a7-b0b6-5b000f3605f1) |
| **Rodapé e Inscrição de Newsletter** | Aplicação da linha delimitadora temática laranja, estilização do formulário de newsletter e bloco de copyright legível. | **Concluído** | [Evidência 09](https://github.com/user-attachments/assets/cf300488-70be-4c14-a31b-ed4c3cec570e) |
| **Auditoria CLI e Integridade Git** | Execução bem-sucedida do comando `setup:di:compile` e saída limpa no `git status` sem alterações no diretório `vendor/magento`. | **Concluído** | [Evidência 10](https://github.com/user-attachments/assets/53166f4d-dd2c-4cb6-b35a-39a34025635e) |

---

## 4. Passo a Passo do Processo de Desenvolvimento

### Passo 1: Estruturação dos Arquivos Base do Tema

* Criação do diretório do tema em `src/app/design/frontend/Webjump/noite-assombrada`.
* Configuração do arquivo `registration.php` utilizando `\Magento\Framework\Component\ComponentRegistrar::THEME`.
* Definição do arquivo `theme.xml` contendo o título legível, a herança do tema pai `Magento/luma` e o caminho relativo para a imagem de pré-visualização `media/preview.jpg`.

### Passo 2: Implementação da Tipografia Local e Variáveis Less

* Inclusão dos arquivos de fonte `creepster-regular.woff2` e `creepster-regular.ttf` na pasta `web/fonts/Creepster/`.
- Criação do arquivo `_theme.less` mapeando as variáveis da identidade visual:
  - Fundo principal da loja: `@color-roxo-noite: #1a0f2b;`
  - Fundo dos cards e contêineres: `@color-roxo-card: #25163e;`
  - Destaque primário e botões: `@color-abobora: #ff6600;`
  - Tipografia decorativa: `@font-creepster: 'Creepster', cursive;`


* Declaração da regra `@font-face` priorizando a extensão `.woff2` para menor peso de transferência e carregamento otimizado com `font-display: swap`.

### Passo 3: Engenharia de Layout e Responsividade

* Configuração das regras Less em `_extend.less` para estilizar a listagem de produtos (`.products-grid .product-item`), aplicando espaçamento interno simétrico de 24 px e bordas sutis com opacidade reduzida (`fade(@color-abobora, 20%)`).
* Aplicação da fonte decorativa nos títulos das páginas (`.page-title`) acompanhada de sombras projetadas para garantir contraste e legibilidade sobre fundos escuros.
* Desenvolvimento de regras mobile (`@media (max-width: 767px)`) para empilhar controles de navegação e expandir o botão de compra para largura total.

### Passo 4: Pipeline de Compilação e Ativação

* Limpeza de arquivos gerados e compilação de assets estáticos no WSL2 com `bin/magento setup:static-content:deploy -f pt_BR en_US` e `bin/magento setup:di:compile`.
* Ativação do tema no painel administrativo em **Content $\rightarrow$ Design $\rightarrow$ Configuration**, selecionando "Noite Assombrada" para a *Default Store View*.

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: Imagem de Preview Não Carregava no Magento Admin

Durante os testes iniciais, o campo **Theme Preview Image** no formulário de edição do tema exibia um quadro cinza ou omitia o campo por completo.

* **Análise e Causa Raiz:** A inspeção do arquivo no sistema operacional revelou que o `preview.jpg` continha apenas **134 Bytes** no disco. Tratava-se de um cabeçalho JPEG corrompido sem o fluxo de pixels reais. Por falhar na leitura do binário, o Magento definiu a coluna `preview_image` da tabela `theme` no banco de dados como `NULL`.

### A Solução: Substituição por Imagem Íntegra e Persistência na Base de Dados

1. **Substituição do Arquivo:** Exportação de um arquivo JPEG íntegro com tamanho de 121 KB para o diretório de media do tema:
`src/app/design/frontend/Webjump/noite-assombrada/media/preview.jpg`
2. **Publicação no Diretório Estático:** Cópia manual para a pasta pública acessada pelo painel administrativo:
`src/pub/media/theme/preview/preview_image_noite_assombrada.jpeg`
3. **Sincronização no Banco de Dados:** Atualização direta do registro na base de dados para restabelecer a referência da imagem:
```bash
bin/mysql -e "UPDATE theme SET preview_image = 'preview_image_noite_assombrada.jpeg' WHERE theme_path = 'Webjump/noite-assombrada';"
chmod -R 777 src/pub/media/theme
bin/magento cache:flush

```

* **Resultado:** O painel administrativo passou a renderizar a miniatura temática corretamente tanto na página de configurações do tema quanto na grade geral de temas.

---

## 6. Estrutura das Regras Less do Tema

Abaixo estão os trechos principais da implementação Less desenvolvida no tema:

### Variáveis e Tipografia Local (`_theme.less` / `_typography.less`)

```less
// Paleta de Cores do Tema Noite Assombrada
@color-roxo-noite: #1a0f2b;
@color-roxo-card:  #25163e;
@color-abobora:    #ff6600;
@color-texto-base: #f3f4f6;

// Declaração da Fonte Local
@font-face {
    font-family: 'Creepster';
    src: url('../fonts/Creepster/creepster-regular.woff2') format('woff2'),
         url('../fonts/Creepster/creepster-regular.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}

```

### Extensões de Layout e Otimizações de UI (`_extend.less`)

```less
// Fundo Global da Loja
body {
    background-color: @color-roxo-noite;
    color: @color-texto-base;
}

// Títulos com Tipografia Temática
.page-title-wrapper .page-title {
    font-family: 'Creepster', cursive;
    color: @color-abobora;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
}

// Card de Produto Simétrico na PLP
.products-grid .product-item {
    background-color: @color-roxo-card;
    border: 1px solid fade(@color-abobora, 20%);
    border-radius: 8px;
    padding: 24px;
}

// Ajuste para Dispositivos Móveis
@media (max-width: 767px) {
    .product-item-actions .action.tocart {
        width: 100%;
        display: block;
        text-align: center;
        background-color: @color-abobora;
    }
}

```

---

## 7. Decisões de Engenharia Frontend: Conformidade e Performance

* **Por que hospedar as fontes localmente em vez de usar CDNs externas?**
Servir os arquivos `.woff2` diretamente pelo Magento elimina chamadas DNS e conexões TLS adicionais no carregamento inicial, previne bloqueio de renderização (*render-blocking*) e garante que o tema continue funcionando mesmo em ambientes de desenvolvimento isolados ou sem conexão com a internet.
* **Por que herdar do `Magento/luma` em vez do `blank`?**
A herança a partir do Luma fornece componentes essenciais prontos para uso comercial (como o mini-cart interativo, os menus suspensos e a galeria de produtos), permitindo concentrar a engenharia no desenvolvimento da identidade visual temático-festiva e nas melhorias de layout.
* **Por que usar `_extend.less` em vez de sobrescrever arquivos inteiros do Luma?**
A extensão de regras de estilo é o padrão recomendado pela Adobe para garantir sustentabilidade a longo prazo. Sobrescrever arquivos Less completos aumenta a complexidade de manutenção e pode quebrar compatibilidade em atualizações de versão do Magento.

---

## 8. Relatório Detalhado de Quality Assurance (QA)

As 10 evidências a seguir comprovam a validação funcional, a responsividade e a integridade técnica da entrega. Os links utilizam as imagens hospedadas no CDN de Issues do GitHub:

### Evidência 01 — Arquitetura de Código: Estrutura do Tema no VS Code

* **Análise Visual:** Comprova a hierarquia de diretórios em `src/app/design/frontend/Webjump/noite-assombrada` com as pastas `media`, `web/css/source` e `web/fonts/Creepster` organizadas. O arquivo `theme.xml` aberto ao lado confirma a herança direta do `Magento/luma`.

<img width="1490" height="692" alt="Evidência 01" src="https://github.com/user-attachments/assets/c6d55a6d-9d56-4d51-aaeb-eab8e23978ea" />

---

### Evidência 02 — Registro no Sistema: Grade de Temas do Magento Admin

* **Análise Visual:** Grade oficial do painel administrativo em **Content $\rightarrow$ Design $\rightarrow$ Themes** (`system_design_theme/index`) listando os 3 registros do ambiente e confirmando a cadeia de herança (`Magento Blank` $\rightarrow$ `Magento Luma` $\rightarrow$ `Noite Assombrada`).

<img width="1916" height="1026" alt="Evidência 02" src="https://github.com/user-attachments/assets/726b4cdc-aba7-46ae-9f88-8ef71a249536" />

---

### Evidência 03 — Ativação de Tema: Design Configuration na Store View

* **Análise Visual:** Configuração de design no painel administrativo em **Content $\rightarrow$ Design $\rightarrow$ Configuration**, demonstrando o tema "Noite Assombrada" ativo no campo *Applied Theme* sob o escopo *Default Store View*.

<img width="1912" height="1027" alt="Evidência 03" src="https://github.com/user-attachments/assets/52741d88-86cd-4472-8b1b-f4a2f4ac6985" />

---

### Evidência 04 — Performance e Tipografia: Arquivo Local Creepster HTTP 200 OK

* **Análise Visual:** Inspeção na aba *Network* do DevTools (filtro Font) comprovando o carregamento do arquivo `creepster-regular.woff2` local com status HTTP 200 OK, sem dependência de serviços externos.

<img width="1917" height="1027" alt="Evidência 04" src="https://github.com/user-attachments/assets/10978418-b69c-49fa-863f-2490474511fc" />

---

### Evidência 05 — Catálogo Desktop: Listagem de Produtos (PLP) com Margem de 24 px

* **Análise Visual:** Página de catálogo da categoria Periféricos em tela cheia desktop, exibindo o título estilizado na fonte Creepster, paleta roxa e os cards de produto com espaçamento simétrico de 24 px sem distorções visuais.

<img width="1917" height="1025" alt="Evidência 05" src="https://github.com/user-attachments/assets/075b77ec-b760-4383-8b35-5b8f068afff9" />

---

### Evidência 06 — Responsividade Mobile-First: Catálogo Adaptado (iPhone 16 / 393 px)

* **Análise Visual:** Simulação mobile no DevTools demonstrando a adaptação completa da listagem, com navegação compacta, contraste preservado e o botão *Add to Cart* preenchendo 100% da largura.

<img width="1912" height="1025" alt="Evidência 06" src="https://github.com/user-attachments/assets/4ca64b07-b6ed-4078-ae9b-15257c5b1241" />

---

### Evidência 07 — Interface Global: Página Inicial (Home Page)

* **Análise Visual:** Home Page da loja com aplicação do fundo escuro e tipografia temática no título principal, preservando a integridade dos blocos de conteúdo e widgets desenvolvidos em sprints anteriores.

<img width="1917" height="1025" alt="Evidência 07" src="https://github.com/user-attachments/assets/9dbd56fe-56c4-4a27-be5a-4dcb643605bc" />

---

### Evidência 08 — Página de Detalhes do Produto (PDP): Mouse Gamer Pro Wireless

* **Análise Visual:** Página de produto individual (`mouse-gamer-pro-wireless.html`) com URL visível, galeria integrada ao fundo escuro, preço destacado em laranja, botão estilizado e atributos de catálogo (EAV) visíveis na seção *More Information*.

<img width="1915" height="1027" alt="Evidência 08" src="https://github.com/user-attachments/assets/b99d88d0-9212-47a7-b0b6-5b000f3605f1" />

---

### Evidência 09 — Componentes de Rodapé: Newsletter e Delimitador Temático

* **Análise Visual:** Seção inferior da loja destacando a linha horizontal laranja abóbora, o formulário de cadastro de newsletter estilizado e a barra de direitos autorais legível sobre fundo escuro.

<img width="1917" height="1027" alt="Evidência 09" src="https://github.com/user-attachments/assets/cf300488-70be-4c14-a31b-ed4c3cec570e" />

---

### Evidência 10 — Auditoria CLI e Git: Compilação de Injeção de Dependências

* **Análise Visual:** Terminal do WSL2 validando o sucesso da compilação com `setup:di:compile` e o comando `git status` atestando que os arquivos do core do Magento (`vendor/magento`) permaneceram intocados.

<img width="895" height="327" alt="Evidência 10" src="https://github.com/user-attachments/assets/53166f4d-dd2c-4cb6-b35a-39a34025635e" />