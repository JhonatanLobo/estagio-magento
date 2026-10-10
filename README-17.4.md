# Implementação Técnica Magento 2: Content Type Customizado "Caixão de Ofertas" no Page Builder

## 1. Visão Geral do Entregável

Este repositório e Pull Request formalizam a entrega técnica do **Desafio 17.4 (Caixão de Ofertas no Page Builder)** da Sprint 8 (Magento 2 / Adobe Commerce). O objetivo central consistiu em projetar, arquitetar e homologar um **content type próprio** no Page Builder, entregando ao time de marketing uma ferramenta visual autossuficiente para diagramar, configurar e publicar blocos promocionais da campanha Noite Assombrada sem qualquer intervenção técnica.

A implementação apoia-se em cinco pilares fundamentais de engenharia de software corporativa:

1. **Módulo Dedicado e Isolado (`Webjump_CaixaoDeOfertas`):** Todo o ciclo de vida do content type vive em módulo próprio, com `<sequence>` declarando dependência de `Magento_PageBuilder`. Zero alterações no `vendor/` ou no tema pai `Magento/luma`.
2. **Declaração XML Estrita ao Schema do Page Builder:** Configuração em `view/adminhtml/pagebuilder/content_type/caixao_ofertas.xml` respeitando o `content_type.xsd`, com aparência `default` única e elementos mapeados ao DOM via readers/converters nativos da plataforma.
3. **Formulário UI Component Estendendo `pagebuilder_base_form`:** Herança integral dos mecanismos de serialização, data source e persistência do Page Builder, com 4 campos configuráveis (Título obrigatório, Descrição, Imagem com uploader nativo e Link).
4. **Preview e Master Separados:** `preview.html` renderiza no canvas do editor com a classe obrigatória `pagebuilder-content-type`; `master.html` gera o HTML persistido no campo `content` da página CMS — mesmo conteúdo, dois contextos de execução.
5. **Estilo em Duas Camadas (Padrão Adobe):** LESS estrutural dentro do módulo (usando variáveis da Magento UI Library, neutro em qualquer tema) e LESS de identidade no tema `Webjump/noite-assombrada` (aplicando paleta e tipografia temática).

---

## 2. Arquitetura e Organização de Arquivos do Projeto

```text
src/app/code/Webjump/CaixaoDeOfertas/
├── registration.php                                              # Registro do módulo
├── etc/
│   └── module.xml                                                # Sequence: Magento_PageBuilder
└── view/
    ├── adminhtml/
    │   ├── layout/
    │   │   ├── default.xml                                       # Injeta o CSS do ícone no admin
    │   │   └── caixao_ofertas_form.xml                           # Handle do modal de edição
    │   ├── pagebuilder/
    │   │   └── content_type/
    │   │       └── caixao_ofertas.xml                            # Declaração do content type
    │   ├── ui_component/
    │   │   └── caixao_ofertas_form.xml                           # Formulário com 4 campos
    │   └── web/
    │       ├── css/
    │       │   └── admin-caixao-ofertas.css                      # Ícone no painel
    │       └── template/
    │           └── content-type/
    │               └── caixao-ofertas/
    │                   └── default/
    │                       ├── preview.html                      # Preview reativo no editor
    │                       └── master.html                       # HTML persistido na loja
    └── frontend/
        └── web/
            └── css/
                └── source/
                    ├── _module.less
                    └── content-type/
                        └── caixao_ofertas/
                            └── _default.less                     # Estrutura visual (UI Library)

src/app/design/frontend/Webjump/noite-assombrada/web/css/source/
├── _extend.less                                                  # Importa o LESS do componente
└── components/
    └── _pagebuilder-caixao-ofertas.less                          # Identidade Halloween
```

---

## 3. Rastreabilidade dos Critérios de Aceite

| Critério | Solução Técnica | Status | Evidência |
|----------|-----------------|--------|-----------|
| O componente aparece no painel, na seção escolhida | `menu_section="elements"` + ícone via CSS `::before` com `\26B0` | Concluído | 01 |
| Dá para arrastar, configurar e ver resultado no editor | `<parents>` liberando row e column + `preview.html` reativo via Knockout | Concluído | 02, 03 |
| O que é configurado no editor aparece na loja | `master.html` com diretivas `if` para não renderizar nós vazios; persistência em `cms_page.content` | Concluído | 05 |
| `pagebuilder-content-type` no elemento externo do preview | Classe declarada como primeira no nó raiz de `preview.html` | Concluído | 04 |
| Estilo segue a identidade do tema | Split: LESS estrutural no módulo + LESS de identidade no tema usando `@color-abobora`, `@color-roxo-noite`, `@color-verde-bruxa`, fonte Creepster | Concluído | 05, 06 |
| Página de campanha publicada | CMS Page `/ofertas-assombradas` montada via Page Builder | Concluído | 07 |
| Integridade do core | `git status` restrito ao módulo | Concluído | 08 |

---

## 4. Passo a Passo do Processo de Desenvolvimento

### Passo 1: Estruturação Base do Módulo

- Criação do diretório `src/app/code/Webjump/CaixaoDeOfertas` com `registration.php` registrando `ComponentRegistrar::MODULE`.
- `etc/module.xml` com `<sequence><module name="Magento_PageBuilder"/></sequence>` garantindo que o Page Builder seja carregado antes do content type.

### Passo 2: Declaração do Content Type

- Configuração em `view/adminhtml/pagebuilder/content_type/caixao_ofertas.xml` com:
  - `name="caixao_ofertas"` (identificador interno snake_case)
  - `label="Caixão de Ofertas"` (rótulo PT-BR no painel)
  - `menu_section="elements"` (seção nativa)
  - `preview_component="Magento_PageBuilder/js/content-type/preview"` (nativo, sem JS custom)
  - Elementos mapeados ao DOM: `main`, `image`, `link`, `empty_link`, `title`, `text`.

### Passo 3: Formulário UI Component

- Criação de `view/adminhtml/ui_component/caixao_ofertas_form.xml` estendendo `pagebuilder_base_form`.
- 4 campos configurados: `title` (input obrigatório), `text` (textarea), `image` (imageUploader), `link_url` (urlInput).

### Passo 4: Templates Knockout

- `preview.html` com a classe obrigatória `pagebuilder-content-type pagebuilder-caixao-ofertas type-nested` no nó raiz.
- `master.html` com diretivas `if` para não renderizar nós vazios quando título, texto ou imagem estiverem em branco.

### Passo 5: Estilização em Duas Camadas

- LESS estrutural no módulo com variáveis da Magento UI Library.
- LESS de identidade no tema `Webjump/noite-assombrada/web/css/source/components/_pagebuilder-caixao-ofertas.less`, importado pelo `_extend.less`.

### Passo 6: Pipeline de Compilação

- `setup:upgrade` → registro do módulo
- `setup:di:compile` → 100% sem erros
- `setup:static-content:deploy -f pt_BR en_US` → publicação dos estáticos e compilação do LESS

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: Falha de Compilação do LESS Estrutural

**Sintoma:** Durante o `setup:static-content:deploy`, a compilação abortou com `variable @border-radius__base is undefined in file .../content-type/caixao_ofertas/_default.less`.

**Causa Raiz:** A variável `@border-radius__base` não existe na Magento UI Library. A biblioteca disponibiliza apenas `@button__border-radius` (específica para botões).

**A Solução:** Substituímos o valor genérico por `4px` direto onde fazia sentido estrutural, mantendo `@button__border-radius` exclusivamente no botão de CTA.

### O Problema: Ícone Customizado no Painel

**Sintoma:** O content type aparecia sem ícone na seção Elements.

**A Solução:** Criamos `view/adminhtml/web/css/admin-caixao-ofertas.css` com a classe `.icon-pagebuilder-caixao-ofertas::before` renderizando o caractere unicode `\26B0` via content CSS, e carregamos o arquivo via `view/adminhtml/layout/default.xml`.

---

## 6. Decisões de Engenharia Frontend

**Por que utilizar o `preview_component` nativo em vez de criar um JS custom?** O componente nativo `Magento_PageBuilder/js/content-type/preview` entrega nativamente o menu de opções (engrenagem, duplicar, excluir), o drag handle, o indicador de seleção e as transições reativas. Criar um JS custom apenas para "não fazer nada além do nativo" adicionaria código morto e pontos de falha. A regra sênior é: só estenda quando houver comportamento específico a adicionar.

**Por que dividir o LESS entre módulo e tema?** O módulo entrega o content type neutro (estrutura, espaçamento, tipografia base com variáveis da UI Library). O tema aplica a identidade da campanha (paleta Noite Assombrada, fonte Creepster). Isso permite que o mesmo content type seja reutilizado em outros temas sem refatoração — padrão Adobe recomendado.

**Por que o `master.html` usa `<div if="data.link.attributes().href">` no nó externo?** Para que o card inteiro seja clicável quando houver link configurado. Se não houver link, o `<div>` renderiza sem âncora, evitando `href=""` vazio que causaria navegação indesejada.

---

## 7. Relatório Detalhado de Quality Assurance (QA)

### Evidência 01 — Componente no Painel do Page Builder

**Análise Visual/Técnica:** Barra lateral esquerda do editor Page Builder exibindo a seção Elements com "Caixão de Ofertas" e ícone, comprovando a declaração do content type e o carregamento do CSS customizado do ícone.

![Evidência 01](https://github.com/user-attachments/assets/24a83a7f-7426-4e3d-af93-3cc4075b0cc6)

### Evidência 02 — Modal de Configuração Preenchido

**Análise Visual/Técnica:** Modal "Editar Caixão de Ofertas" com os 4 campos preenchidos — Título "Promoção Fantasma", Descrição, Imagem "caveira.png, 600x593, 73 KB" e Link `https://magento.test/perifericos.html`. Comprova a hidratação do formulário e o funcionamento do uploader nativo.

![Evidência 02](https://github.com/user-attachments/assets/ea8e99f6-394b-4418-8ff6-a70ef35920c9)

### Evidência 03 — Preview Reativo no Canvas do Editor

**Análise Visual/Técnica:** Tela de edição da página CMS com o Page Builder renderizando os 2 cards lado a lado (crânio e fantasma) no canvas, provando que o `preview.html` reativo funciona e o conteúdo configurado é exibido em tempo real.

![Evidência 03](https://github.com/user-attachments/assets/ba1ee5ad-935a-44a0-92d9-5a3445ab49f9)

### Evidência 04 — Inspeção da Classe `pagebuilder-content-type` no DOM

**Análise Visual/Técnica:** DevTools Elements com o breadcrumb inferior destacando `div.pagebuilder-content-type.pagebuilder-caixao-ofertas.type-nested`, e o campo de busca confirmando 2 ocorrências (`2 of 2`) no DOM. Cumpre estritamente o critério de aceite "A classe `pagebuilder-content-type` está no elemento externo do preview".

![Evidência 04](https://github.com/user-attachments/assets/c054a8a6-6f6a-4b08-9546-288fa57a7fe9)

### Evidência 05 — Página de Campanha Publicada (Frontend Desktop)

**Análise Visual/Técnica:** Página `/ofertas-assombradas` renderizada no frontend com URL visível, breadcrumb `Home > Ofertas Assombradas`, os 2 cards com identidade Halloween aplicada (fundo roxo gradiente, borda abóbora, título em Creepster, botão "VER OFERTA" arredondado) e o rodapé institucional do tema integrado.

![Evidência 05](https://github.com/user-attachments/assets/d4e42610-4814-4ca0-b06d-d3bb00a82949)

### Evidência 06 — Responsividade Mobile-First

**Análise Visual/Técnica:** Emulação mobile no DevTools (iPhone 16 Pro Max / 440px) demonstrando o empilhamento vertical dos cards, imagem centralizada no topo, título e descrição abaixo, e botão "VER OFERTA" ocupando 100% da largura útil.

![Evidência 06](https://github.com/user-attachments/assets/38d7419c-415d-42d1-b6a2-d8e739e5f7d3)

### Evidência 07 — Página CMS Criada e Publicada no Admin

**Análise Visual/Técnica:** Grade de páginas CMS em `Content > Pages` listando "Ofertas Assombradas" com URL Key `ofertas-assombradas`, Layout `Page -- Full Width`, Store View `All Store Views` e Status `Enabled`.

![Evidência 07](https://github.com/user-attachments/assets/8d5bd9b1-19a2-4863-9440-4160eb8eb303)

### Evidência 08 — Integridade de Compilação e Inviolabilidade do Core

**Análise Visual/Técnica:** Terminal do WSL2 executando `setup:di:compile && git status -uall`. Comprova compilação finalizada em 100% (32 segundos), lista os 12 arquivos do módulo/tema na branch `exercicio/17.4-caixao-ofertas-pagebuilder`, e confirma `vendor/` intocado.

![Evidência 08](https://github.com/user-attachments/assets/223c9b70-d93c-4076-bb1e-6adde16cfe0c)
