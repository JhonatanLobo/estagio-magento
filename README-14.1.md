# Extensão de Catálogo no Magento 2: Atributo EAV via Data Patch e Renderização Defensiva na PDP

## 1. Visão Geral do Entregável

Este documento e Pull Request formalizam a entrega técnica do Desafio 14.1 (Atributo de produto por código) da Sprint 7 (Magento 2 / Adobe Commerce). O objetivo principal foi estender a camada de dados do catálogo provisionando programaticamente o atributo Selo Sustentável (selo_sustentavel) e acoplando sua exibição na Página de Detalhes do Produto (PDP) sem nenhuma intervenção manual de banco de dados em produção e preservando a integridade estrita do diretório vendor/.

A implementação apoia-se em quatro pilares arquiteturais de engenharia:

1. **Modelagem EAV Declarativa e Idempotente (DataPatchInterface):** Provisionamento do atributo através de Data Patch versionado, garantindo execução única via registro na tabela patch_list do MariaDB e reprodutibilidade idêntica em esteiras de deploy automatizadas.
2. **Governança Granular de Escopo (SCOPE_STORE):** Atribuição do escopo em nível de Store View, viabilizando autonomia regional para conformidades ecológicas sem impactar operações globais.
3. **Desacoplamento Visual e Padrão ViewModel (ArgumentInterface):** Isolamento absoluto das regras de consulta na classe ProductBadge, mantendo o template .phtml estritamente focado em marcação semântica.
4. **Programação Defensiva e Blindagem contra XSS:** Supressão total de nós no DOM caso o atributo esteja inativo ou nulo (prevenção de containers vazios) e sanitização mandatória via `$block->escapeHtml()` e `$block->escapeHtmlAttr()`.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore completa de artefatos estruturados no módulo `src/app/code/Webjump/Jhonatan/`, evidenciando a separação entre patches de dados, regras de apresentação e layout:

```text
src/app/code/Webjump/Jhonatan/
├── registration.php                                       # Registro do componente no ComponentRegistrar
├── etc/
│   ├── module.xml                                         # Declaração do módulo e sequência de boot
│   ├── frontend/
│   │   └── di.xml                                         # Interceptors de vitrine (Sprint 6)
│   └── adminhtml/
│       └── events.xml                                     # Observers administrativos (Sprint 6)
│
├── Setup/
│   └── Patch/
│       └── Data/
│           └── AddSeloSustentavelProductAttribute.php     # Data Patch para provisionamento do atributo EAV
│
├── ViewModel/
│   ├── HomeBlock.php                                      # Lógica do bloco da Home (Sprint 6)
│   └── ProductBadge.php                                   # Resolução defensiva do selo na PDP
│
└── view/
    └── frontend/
        ├── layout/
        │   ├── cms_index_index.xml                        # Orquestração da Home
        │   └── catalog_product_view.xml                   # Injeção declarativa do badge antes do preço
        ├── templates/
        │   ├── home_block.phtml                           # Template institucional da Home
        │   └── product/
        │       └── badge.phtml                            # Renderização semântica e limpa do selo
        └── web/
            └── css/
                ├── home-block.css                         # Estilização da Home
                └── product-badge.css                      # Estilização modular e responsiva do badge
```

---

## 3. Rastreabilidade dos Critérios de Aceite

A tabela a seguir correlaciona os requisitos avaliativos da Sprint com as implementações técnicas realizadas e as respectivas evidências comprobatórias:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
|---|---|---|---|
| Atributo Criado via Patch | Criação da classe AddSeloSustentavelProductAttribute aplicando addAttribute() na entidade catalog_product. | Concluído | Evidências 01 e 02 |
| Idempotência no Banco | Registro automático da classe na tabela patch_list durante a execução do setup:upgrade. | Concluído | Evidência 01 |
| Exibição no Admin | Atributo alocado programaticamente no grupo General do Attribute Set Default com rótulo em português. | Concluído | Evidências 03, 04 e 05 |
| Persistência de Catálogo | Associação funcional do atributo na ficha cadastral do produto Mouse Gamer Pro Wireless. | Concluído | Evidências 06 e 10 |
| Renderização na PDP | Injeção no container product.info.main antes de product.info.price via Layout XML e ViewModel. | Concluído | Evidências 07, 08 e 09 |
| Programação Defensiva | Validação estrita via $viewModel->isSustentavel(), garantindo ausência de nós órfãos no DOM quando inativo. | Concluído | Evidência 11 |
| Justificativa de Escopo | Relato analítico detalhando a escolha de SCOPE_STORE frente a GLOBAL e WEBSITE. | Concluído | Seção 6 deste doc |
| Inviolabilidade do Core | Desenvolvimento estritamente isolado em módulo próprio sem alterações em vendor/. | Concluído | Evidência 12 |

---

## 4. Passo a Passo do Processo de Desenvolvimento

### Passo 1: Construção do Data Patch (Setup/Patch/Data/AddSeloSustentavelProductAttribute.php)

- Implementou-se a classe sob o contrato Magento\Framework\Setup\Patch\DataPatchInterface.
- Injetou-se EavSetupFactory sobre ModuleDataSetupInterface para manipular o dicionário de entidades EAV.
- No método apply(), provisionou-se o atributo selo_sustentavel com:
  - type => 'int', input => 'boolean' e source model Magento\Eav\Model\Entity\Attribute\Source\Boolean.
  - global => ScopedAttributeInterface::SCOPE_STORE para controle granular por Store View.
  - group => 'General' para vinculação direta na primeira aba do formulário de edição do produto.
  - used_in_product_listing => true e visible_on_front => true para disponibilizar o valor na camada pública.

### Passo 2: Construção do ViewModel (ViewModel/ProductBadge.php)

- Implementou-se a interface Magento\Framework\View\Element\Block\ArgumentInterface.
- Injetou-se o serviço Magento\Framework\Registry para capturar a entidade current_product em runtime de forma segura.
- Implementou-se o método booleano isSustentavel(), assegurando retorno falso caso o produto seja nulo ou o valor do atributo seja zero.
- Mapearam-se métodos com tipagem estrita para rótulo institucional, tooltip de acessibilidade e o path vetorial da folha orgânica.

### Passo 3: Injeção Estrutural via Layout XML (view/frontend/layout/catalog_product_view.xml)

- Mapeou-se o handle nativo da PDP (catalog_product_view).
- Injetou-se a folha de estilos dedicada Webjump_Jhonatan::css/product-badge.css no container `<head>`.
- Sob o container product.info.main, instanciou-se o bloco webjump.product.badge.sustentavel apontando para o template product/badge.phtml, posicionado antes de product.info.price (before="product.info.price").
- Injetou-se o ViewModel via nó `<argument name="view_model" xsi:type="object">`.

### Passo 4: Template Semântico e Sanitização (view/frontend/templates/product/badge.phtml)

- Aplicou-se guarda defensiva estrita com instanceof e verificação de $viewModel->isSustentavel().
- Utilizou-se marcação semântica encapsulada por role="status" para tecnologias assistivas.
- O ícone vetorial inline recebeu aria-hidden="true" para não poluir leitores de tela.
- Todos os nós de texto e atributos dinâmicos foram protegidos por $block->escapeHtml() e $block->escapeHtmlAttr().

### Passo 5: Estilização Isolada e Pipeline de Build

- O arquivo product-badge.css foi estruturado sob paleta verde esmeralda (#ecfdf5 / #065f46), com bordas arredondadas e alinhamento flexível.
- No terminal do WSL2, disparou-se a esteira bin/magento setup:upgrade, bin/magento setup:di:compile e purga de caches para persistir o patch e gerar os interceptores da aplicação.

---

## 5. Decisões de Engenharia e Resolução de Problemas (Troubleshooting)

### 1. Modelo EAV e Mitigação de Migrações Destrutivas

**Cenário:** Em bancos relacionais legados, a criação de novos campos no produto envolvia scripts manuais com comandos ALTER TABLE, arriscando indisponibilidade ou bloqueio de tabelas em bases com milhares de SKUs.

**Solução Adotada:** Utilização do modelo EAV acionado por Data Patch. A criação do campo insere metadados em eav_attribute e delega a gravação para a tabela especializada catalog_product_entity_int, mantendo o banco operacional e sem lock de tabelas.

### 2. Blindagem contra Renderização de Containers Vazios no DOM

**Cenário:** Templates desenvolvidos sem validação prévia renderizam tags vazias (ex.: `<div class="badge-wrapper"></div>`) quando o atributo não está preenchido, gerando saltos de layout (Cumulative Layout Shift - CLS) e violando as diretrizes de código limpo.

**Solução Adotada:** A condicional `if ($viewModel instanceof ... && $viewModel->isSustentavel())` engloba todo o wrapper exterior. Se o produto não possui a flag ativa, o template devolve saída nula, mantendo o DOM limpo (Auditado na Evidência 11).

### 3. Vetor SVG Calibrado vs Emojis/Ícones Desproporcionais

**Cenário:** Protótipos iniciais com ícones vetoriais genéricos ou caracteres fora de proporção causavam distorções verticais no alinhamento da linha de preço.

**Solução Adotada:** Mapeamento de um path vetorial de folha orgânica projetado para o grid viewBox="0 0 24 24" com espessura uniforme (stroke-width="2"), alinhamento flex central e tamanho fixado em 15x15px.

---

## 6. Justificativa Arquitetural de Escopo: Por que SCOPE_STORE?

A escolha do escopo de um atributo EAV é uma das decisões estruturais mais importantes em arquitetura de e-commerce corporativo. O Magento organiza os atributos em três níveis:

| Escopo | Nível de Governança | Impacto Técnico no Sistema |
|---|---|---|
| SCOPE_GLOBAL | Instalação Inteira | O valor é único e idêntico em todos os Websites, Lojas e Store Views da plataforma. |
| SCOPE_WEBSITE | Unidade de Negócio | O valor varia de acordo com o Website comercial (preços, inventário ou clientes). |
| SCOPE_STORE | Visão da Loja / Idioma | O valor pode ser sobrescrito por Store View (idioma, regionalização e apresentação visual). |

### Racional da Escolha por SCOPE_STORE no Selo Sustentável

Para o atributo Selo Sustentável, adotou-se estritamente ScopedAttributeInterface::SCOPE_STORE pelos seguintes fundamentos técnicos:

- **Assimetria Regulatória e de Certificação Regional:** Selos de sustentabilidade, selos de eficiência energética e certificações ecológicas não são universais. Um produto que atende aos critérios regulatórios de sustentabilidade para o mercado europeu pode não possuir homologação fiscal para ostentar o mesmo selo no mercado brasileiro, e vice-versa. Definir o escopo como SCOPE_GLOBAL forçaria o selo a aparecer em todos os países ou em nenhum, gerando passivo jurídico de conformidade publicitária.
- **Prevenção do Bug de Sobrescrita em Cascata:** Atributos globais alteram o registro em cascata para toda a plataforma. Se um operador administrativo da filial brasileira ativasse o selo no produto acreditando estar customizando apenas a loja local, a alteração sobrescreveria imediatamente os catálogos dos Estados Unidos e de outros mercados da empresa.
- **Autonomia de Marketing por Canal:** Com SCOPE_STORE, o catálogo mantém a mesma entidade física compartilhando estoque e logística, mas permite que os times comerciais de cada operação regional ativem campanhas verdes ou desativem o selo conforme a estratégia de cada vitrine.

---

## 7. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as 12 evidências formais de homologação do Desafio 14.1, auditando a camada de dados no MariaDB, o dicionário EAV no admin, o formulário de catálogo, a renderização na vitrine pública e a programação defensiva:

### Evidência 01 — Registro de Idempotência na Tabela patch_list (CLI)

**Análise Visual/Técnica:** Retorno da query SQL no banco MariaDB executada via CLI (SELECT patch_id, patch_name FROM patch_list...). Comprova a persistência do patch sob o ID 185 com o namespace exato da classe, assegurando que o framework não reexecutará a rotina em upgrades subsequentes.

<img width="100%" alt="Evidência 01 — Registro de Idempotência na Tabela patch_list (CLI)" src="https://github.com/user-attachments/assets/47b55481-a5ac-4592-bc9e-8f0cae4c82f4" />

---

### Evidência 02 — Metadados EAV na Tabela eav_attribute (CLI)

**Análise Visual/Técnica:** Consulta no banco comprovando a modelagem relacional do atributo selo_sustentavel: registrado sob o ID 137, com backend_type = int, frontend_input = boolean e flag is_user_defined = 1, confirmando conformidade com a arquitetura EAV nativa.

<img width="100%" alt="Evidência 02 — Metadados EAV na Tabela eav_attribute (CLI)" src="https://github.com/user-attachments/assets/9dce0c48-d5c6-4eda-a684-090081c962f0" />

---

### Evidência 03 — Atributo no Grid de Atributos do Admin

**Análise Visual/Técnica:** Interface administrativa em Stores > Attributes > Product filtrada por selo_sustentavel. Evidencia o rótulo padrão "Selo Sustentável", status visível, ausência de obrigatoriedade e escopo demarcado como Store View.

<img width="100%" alt="Evidência 03 — Atributo no Grid de Atributos do Admin" src="https://github.com/user-attachments/assets/a380a79f-45af-4f57-aa0c-a28dbcb1faef" />

---

### Evidência 04 — Painel de Propriedades Avançadas do Atributo

**Análise Visual/Técnica:** Ficha cadastral do atributo no admin exibindo Catalog Input Type for Store Owner como Yes/No e a confirmação em Advanced Attribute Properties do escopo travado em Store View.

<img width="100%" alt="Evidência 04 — Painel de Propriedades Avançadas do Atributo" src="https://github.com/user-attachments/assets/8b5336d7-97ba-44cf-b534-63838ecc0195" />

---

### Evidência 05 — Alocação no Grupo General do Attribute Set

**Análise Visual/Técnica:** Configuração do conjunto de atributos em Stores > Attributes > Attribute Set (Default). Demonstra a injeção programática do atributo selo_sustentavel dentro da pasta de agrupamento General.

<img width="100%" alt="Evidência 05 — Alocação no Grupo General do Attribute Set" src="https://github.com/user-attachments/assets/540f5940-3b7f-4134-8f16-deb21e5c4442" />

---

### Evidência 06 — Persistência do Atributo Ativo no Produto (Admin)

**Análise Visual/Técnica:** Painel Catalog > Products na ficha do Mouse Gamer Pro Wireless. Comprova o seletor Selo Sustentável ativo em Yes, escopo contextual [store view] e a notificação de confirmação ("You saved the product.").

<img width="100%" alt="Evidência 06 — Persistência do Atributo Ativo no Produto (Admin)" src="https://github.com/user-attachments/assets/232e2765-692c-4695-b83f-f2e7c7273563" />

---

### Evidência 07 — Renderização do Selo na Vitrine Desktop (PDP Ativa)

**Análise Visual/Técnica:** Vitrine pública acessada em [https://magento.test/mouse-gamer-pro-wireless.html](https://magento.test/mouse-gamer-pro-wireless.html). Comprova a renderização do badge institucional em verde esmeralda com o ícone vetorial de folha posicionado exatamente acima do bloco de preço.

<img width="100%" alt="Evidência 07 — Renderização do Selo na Vitrine Desktop (PDP Ativa)" src="https://github.com/user-attachments/assets/36103c53-1aec-498f-aa30-e02cc1af375e" />

---

### Evidência 08 — Inspeção Semântica e Acessibilidade no DOM (DevTools)

**Análise Visual/Técnica:** Inspecionador do navegador na aba Elements. Valida a estrutura semântica composta por div.webjump-product-badge-wrapper portando role="status", span descritivo e o elemento `<svg>` inline protegido por aria-hidden="true".

<img width="100%" alt="Evidência 08 — Inspeção Semântica e Acessibilidade no DOM (DevTools)" src="https://github.com/user-attachments/assets/4162b3d2-94e5-408a-9b8e-f53d225ea82d" />

---

### Evidência 09 — Renderização Responsiva Mobile

**Análise Visual/Técnica:** Simulação de visualização móvel em viewport reduzido (390px). Comprova que a folha de estilos product-badge.css mantém a pílula dimensionada e alinhada ao fluxo da página sem quebras de layout.

<img width="100%" alt="Evidência 09 — Renderização Responsiva Mobile" src="https://github.com/user-attachments/assets/ca3c72b9-7c68-4ab9-83f8-16c2b8fa7ab3" />

---

### Evidência 10 — Desativação do Atributo no Catálogo (Admin)

**Análise Visual/Técnica:** Formulário administrativo do produto com a chave seletora Selo Sustentável alternada para No e notificação de salvamento bem-sucedida, demonstrando a mutação de estado pelo operador.

<img width="100%" alt="Evidência 10 — Desativação do Atributo no Catálogo (Admin)" src="https://github.com/user-attachments/assets/1b17d718-131b-4b25-b442-7ec1d1d56507" />

---

### Evidência 11 — Programação Defensiva: Ausência de Nós Órfãos no DOM (PDP Inativa)

**Análise Visual/Técnica:** Inspeção da PDP com o atributo desativado. Demonstra a supressão visual do selo e confirma via árvore do DevTools que nenhuma tag wrapper ou elemento vazio foi injetado no DOM.

<img width="100%" alt="Evidência 11 — Programação Defensiva: Ausência de Nós Órfãos no DOM (PDP Inativa)" src="https://github.com/user-attachments/assets/b2a31ffb-c204-491a-8dc9-96082925d8bb" />

---

### Evidência 12 — Integridade de Compilação e Inviolabilidade do Core (CLI)

**Análise Visual/Técnica:** Terminal evidenciando a conclusão em 100% do compilador de injeção de dependência (bin/magento setup:di:compile) e o retorno do comando git status atestando que o diretório vendor/ permaneceu estritamente intocado.

<img width="100%" alt="Evidência 12 — Integridade de Compilação e Inviolabilidade do Core (CLI)" src="https://github.com/user-attachments/assets/26c8911d-1e39-4ed1-a37d-a3e19b0d496c" />