# Implementação Técnica Magento 2: Grid Administrativo com UI Components, ACL Granular e Ações em Massa

## 1. Visão Geral do Entregável

Este documento e Pull Request formalizam a entrega técnica do Desafio 15.1 (Grid de administração completo) da Sprint 7 (Magento 2 / Adobe Commerce). O objetivo central consistiu em projetar, arquitetar e homologar a camada de interface administrativa para a entidade de avaliações de produtos (`webjump_avaliacao`), fornecendo ao time de moderação e atendimento uma tela corporativa desacoplada, performática e segura para navegação, filtros analíticos, ordenação e operações em lote (Mass Actions), sem intervenção manual no banco de dados e preservando a integridade estrita do diretório `vendor/`.

A implementação apoia-se em cinco pilares fundamentais de engenharia de software corporativa:

**Roteamento Administrativo e Segurança Declarativa (`routes.xml` e `acl.xml`)**: Registro de rotas isoladas no roteador admin com modelagem da árvore de permissões hierárquica na ACL (Access Control List), assegurando governança de acesso e segregação prévia de privilégios para exportação de dados corporativos.

**Guarda Rígida de Acesso em Controladores (`ADMIN_RESOURCE`)**: Aplicação mandatória da constante `ADMIN_RESOURCE` em todos os controllers administrativos, impedindo vetores de invasão por chamada direta de URL (Broken Object Level Authorization) e garantindo o redirecionamento automático de usuários não autorizados.

**Interface Declarativa Reativa (UI Components Listing)**: Construção da listagem através de `webjump_avaliacao_listing.xml`, delegando a renderização visual aos componentes JavaScript nativos do Magento (`listingToolbar`, `filters`, `columns`, `paging`), proporcionando buscas assíncronas e persistência de preferências de visualização.

**Desacoplamento de Dados via Data Provider Especializado (`SearchResult`)**: Extensão da coleção relacional conectada ao DataProvider do framework através do container global de Injeção de Dependências (`etc/di.xml`), alimentando o grid a partir da tabela física `webjump_avaliacao` sem acoplamento a blocos PHP legados.

**Ações em Massa Idempotentes com Service Contracts**: Implementação de controllers especializados (`MassApprove` e `MassDelete`) que consomem o filtro de lote nativo (`Magento\Ui\Component\MassAction\Filter`) e realizam mutações de estado estritamente através do `AvaliacaoRepositoryInterface`, respeitando a integridade do domínio e disparando observers globais.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore completa de artefatos estruturados no módulo `src/app/code/Webjump/Jhonatan/`, demonstrando a separação estrita de responsabilidades entre regras de controle, camadas de dados e componentes visuais administrativos:

```plaintext
src/app/code/Webjump/Jhonatan/
├── registration.php                                                # Registro do componente no ComponentRegistrar
├── etc/
│   ├── module.xml                                                  # Declaração do módulo e sequência de boot
│   ├── events.xml                                                  # Observer global (corrigido do escopo adminhtml)
│   ├── di.xml                                                      # Mapeamento de Service Contracts e DataProvider do Grid
│   ├── acl.xml                                                     # Árvore de permissões e governança de acesso (ACL)
│   ├── db_schema.xml                                               # Definição física da tabela webjump_avaliacao
│   ├── db_schema_whitelist.json                                    # Whitelist declarativa de segurança da tabela
│   └── adminhtml/
│       ├── routes.xml                                              # Mapeamento do frontName para o roteador admin
│       └── menu.xml                                                # Registro dos nós "Webjump > Avaliações" no menu
│
├── Api/
│   ├── AvaliacaoRepositoryInterface.php                            # Service Contract de persistência e consulta
│   └── Data/
│       ├── AvaliacaoInterface.php                                  # Contrato de dados da entidade (getters/setters)
│       └── AvaliacaoSearchResultsInterface.php                     # Contrato para listas paginadas e filtradas
│
├── Controller/
│   └── Adminhtml/
│       └── Avaliacao/
│           ├── Index.php                                           # Controller principal da tela de listagem
│           ├── MassApprove.php                                     # Processamento de aprovação em lote via repositório
│           └── MassDelete.php                                      # Processamento de exclusão em massa com confirmação
│
├── Model/
│   ├── Avaliacao.php                                               # Model da entidade (Active Record / Negócio)
│   ├── AvaliacaoRepository.php                                     # Implementação concreta do Service Contract
│   ├── AvaliacaoSearchResults.php                                  # Implementação concreta dos resultados paginados
│   └── ResourceModel/
│       ├── Avaliacao.php                                           # ResourceModel mapeando tabela e chave primária
│       └── Avaliacao/
│           ├── Collection.php                                      # Coleção relacional padrão para seleções em massa
│           └── Grid/
│               └── Collection.php                                  # Coleção especializada SearchResult para o UI Component
│
├── Setup/
│   └── Patch/
│       └── Data/
│           ├── AddSampleAvaliacoes.php                             # Ingestão idempotente de 5 avaliações de teste
│           └── AddSeloSustentavelProductAttribute.php              # Provisionamento do atributo EAV (Desafio 14.1)
│
├── ViewModel/
│   └── ProductBadge.php                                            # Resolução defensiva do selo na PDP (Desafio 14.1)
│
└── view/
    ├── adminhtml/
    │   ├── layout/
    │   │   └── webjump_avaliacao_avaliacao_index.xml              # Orquestração de injeção do UI Component no admin
    │   └── ui_component/
    │       └── webjump_avaliacao_listing.xml                       # Definição declarativa do grid, colunas e mass actions
    └── frontend/
        ├── layout/
        │   └── catalog_product_view.xml                            # Layout da PDP com o selo sustentável
        ├── templates/
        │   └── product/
        │       └── badge.phtml                                     # Template defensivo do selo na vitrine
        └── web/
            └── css/
                └── product-badge.css                               # Estilização modular e responsiva do selo
```

---

## 3. Rastreabilidade dos Critérios de Aceite

A tabela correlaciona os requisitos avaliativos da especificação com as soluções técnicas implementadas e as respectivas evidências de QA:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
|---|---|---|---|
| Menu Administrativo Funcional | Criação de `etc/adminhtml/menu.xml` vinculando "Webjump > Avaliações" à action `webjump_avaliacao/avaliacao/index`. | Concluído | Evidências 01 e 04 |
| Grid Alimentado por Collection | Configuração de `Grid\Collection` mapeada no `di.xml` e consumida pelo DataProvider nativo do UI Component. | Concluído | Evidências 02 e 05 |
| Conversão Sim/Não no Grid | Associação da source model `Yesno` nativa na coluna `aprovado`, traduzindo inteiros binários para rótulos legíveis. | Concluído | Evidência 06 |
| Filtro Textual Dinâmico | Declaração de filtros do tipo `text` nas colunas `autor` e `comentario` com busca parcial sanitizada. | Concluído | Evidência 07 |
| Filtro Numérico por Intervalo | Implementação de `textRange` nas colunas `avaliacao_id` e `nota`, viabilizando consultas por limites mínimo e máximo. | Concluído | Evidência 08 |
| Filtro Temporal por Intervalo | Mapeamento da coluna `created_at` com `dateRange` e componente visual de calendário nativo do Magento. | Concluído | Evidência 09 |
| Ordenação e Paginação Ativas | Habilitação dos nós `<sorting>` e `<paging>` com suporte a ordenação por clique e seletor de linhas por página. | Concluído | Evidência 10 |
| Ação em Massa: Exclusão Segura | Implementação de `MassDelete` com caixa de diálogo confirmatória nativa (`<confirm>`) prevenindo exclusões acidentais. | Concluído | Evidência 11 |
| Ação em Massa: Aprovação em Lote | Implementação do controller `MassApprove` atualizando o status booleano dos registros via `AvaliacaoRepositoryInterface`. | Concluído | Evidência 12 |
| Árvore de ACL com Segregação | Estruturação de `etc/acl.xml` segregando a permissão do grid (`avaliacao`) da permissão de exportação (`avaliacao_export`). | Concluído | Evidências 01 e 13 |
| Bloqueio a Usuário Restrito | Validação em runtime de `ADMIN_RESOURCE` bloqueando operadores sem a permissão atribuída na role administrativa. | Concluído | Evidência 14 |
| Integridade de Build e Core | Compilação em 100% via `setup:di:compile` e preservação estrita do diretório `vendor/` intocado. | Concluído | Evidências 03 e 15 |

---

## 4. Passo a Passo do Processo de Desenvolvimento

A esteira de construção da interface seguiu a metodologia em camadas recomendada pela Adobe, garantindo robustez e desacoplamento:

### Passo 1: Infraestrutura de Rotas e Navegação Administrativa

Registrou-se a rota em `etc/adminhtml/routes.xml` utilizando o roteador admin e o identificador de front-name `webjump_avaliacao` com a diretiva `before="Magento_Backend"`, garantindo precedência correta de despacho.

Criou-se `etc/adminhtml/menu.xml` declarando o nó pai corporativo `Webjump_Jhonatan::webjump` (ordem 50) e o filho `Webjump_Jhonatan::avaliacao` (ordem 10), apontando diretamente para a URI de rota `webjump_avaliacao/avaliacao/index`.

### Passo 2: Modelagem Granular de Acesso (ACL)

Definiu-se a hierarquia de recursos em `etc/acl.xml` sob o nó raiz `Magento_Backend::admin`.

Separou-se a permissão de visualização e moderação (`Webjump_Jhonatan::avaliacao`) do nó de exportação de dados (`Webjump_Jhonatan::avaliacao_export`), viabilizando a governança onde o time operacional modera o grid sem acesso ao download de relatórios confidenciais.

### Passo 3: Controlador Principal e Layout Handler

Construiu-se `Controller/Adminhtml/Avaliacao/Index.php` estendendo `Magento\Backend\App\Action` e assinando a interface `HttpGetActionInterface`.

Vinculou-se formalmente a constante pública `ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao'`, garantindo que a infraestrutura de segurança do Magento intercepte qualquer tentativa de acesso antes da execução de `execute()`.

Criou-se o layout `view/adminhtml/layout/webjump_avaliacao_avaliacao_index.xml`, injetando declarativamente a tag `<uiComponent name="webjump_avaliacao_listing"/>` dentro do container estrutural `content`.

### Passo 4: Camada de Dados para UI Component (Grid Collection e DI)

Implementou-se `Model/ResourceModel/Avaliacao/Grid/Collection.php` estendendo `Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult`, provendo suporte nativo a paginação por offset, ordenação e filtros dinâmicos.

Mapeou-se no container global de injeção de dependências (`etc/di.xml`) o vínculo entre o nome da fonte de dados (`webjump_avaliacao_listing_data_source`) e a classe concreta da coleção especializada através dos argumentos de `UiComponent\DataProvider\CollectionFactory`.

### Passo 5: Definição Declarativa do UI Component Listing

Desenvolveu-se `view/adminhtml/ui_component/webjump_avaliacao_listing.xml` configurando o dataSource, botão "Nova Avaliação", barra de ferramentas fixa (sticky), controle de colunas, paginação e caixa de busca.

Declararam-se as 8 colunas tipadas:

- `ids`: Coluna de seleção com checkbox vinculada ao índice primário `avaliacao_id`.
- `avaliacao_id`: Identificador numérico com ordenação descendente padrão e filtro `textRange`.
- `product_id`: ID do produto avaliado com filtro `textRange`.
- `autor` e `comentario`: Campos de texto com filtros `text` sanitizados.
- `nota`: Avaliação numérica de 1 a 5 com filtro `textRange`.
- `aprovado`: Componente select consumindo a source model nativa `Magento\Config\Model\Config\Source\Yesno`, traduzindo valores booleanos (1/0) para rótulos amigáveis ("Yes"/"No").
- `created_at`: Data de criação renderizada pelo componente `Magento_Ui/js/grid/columns/date` com filtro de calendário `dateRange`.

### Passo 6: Implementação de Controladores de Ação em Massa

Desenvolveu-se `Controller/Adminhtml/Avaliacao/MassApprove.php` (assinado por `HttpPostActionInterface` e protegido por `ADMIN_RESOURCE`), injetando `Magento\Ui\Component\MassAction\Filter` e `AvaliacaoRepositoryInterface`. O controller itera sobre as linhas selecionadas, define `aprovado = true` e persiste via repositório, emitindo notificações com contadores ao usuário.

Desenvolveu-se `Controller/Adminhtml/Avaliacao/MassDelete.php` consumindo o método `delete()` do repositório, englobado por tratamento defensivo de exceções e precedido pela diretiva declarativa `<confirm>` no XML que invoca a modal de confirmação do Magento antes do disparo da requisição POST.

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: Falha de Compilação por Ausência de Factory (CollectionFactory does not exist)

Durante a execução de `bin/magento setup:di:compile`, a esteira foi abortada com erro fatal na geração de interceptores:

```plaintext
In Generator.php line 140:
  Class "Webjump\Jhonatan\Model\ResourceModel\Avaliacao\CollectionFactory" does not exist
  Class Webjump\Jhonatan\Controller\Adminhtml\Avaliacao\MassDelete\Interceptor generation error...
```

**Causa Raiz**: O compilador do Magento gera dinamicamente classes terminadas em `Factory` a partir de uma classe base real. Como a branch de trabalho `fix/mover-observer-escopo-global` foi inicializada antes da consolidação dos commits do Desafio 14.2 na árvore local de trabalho, o arquivo base `Model/ResourceModel/Avaliacao/Collection.php` constava como apagado localmente no disco, impossibilitando a geração do código proxy pelo autoloader.

**A Solução**: Inspecionou-se o rastreamento do Git com `git ls-files`, confirmando que o arquivo pertencia ao histórico versionado da Sprint 14. Executou-se `git restore src/app/code/Webjump/Jhonatan/Model/ResourceModel/Avaliacao/Collection.php`, recuperando a classe base. Em seguida, executou-se a purga do diretório `generated/code/*` e acionou-se nova compilação via `bin/magento setup:di:compile`, atingindo 100% de sucesso sem falhas de injeção.

### O Problema: Carregamento Infinito na Tela do Grid (this.source is not a function)

Ao acessar pela primeira vez a rota do grid administrativo, a tela permaneceu bloqueada em carregamento contínuo com a mensagem "0 records found". O console do DevTools reportou:

```plaintext
jQuery.Deferred exception: this.source is not a function TypeError: this.source is not a function
    at UiClass.exportSorting (column.js:187:18)
```

**Causa Raiz**: O componente raiz `<listing>` foi declarado sem o argumento estrutural `js_config/provider`, impedindo que as colunas filhas herdassem a referência ao data source durante a inicialização síncrona do Knockout.js (`initLinks`). Adicionalmente, a tag `<sorting>` aplicada prematuramente na coluna `avaliacao_id` tentava invocar `exportSorting()` antes da resolução assíncrona do provider.

**A Solução**: Injetou-se o bloco `<argument name="data">` declarando explicitamente o provider no topo de `webjump_avaliacao_listing.xml`. Removeu-se a ordenação síncrona imediata, purgaram-se os estados corrompidos gravados na tabela `ui_bookmark` do MariaDB com `DELETE FROM ui_bookmark WHERE namespace = 'webjump_avaliacao_listing';` e limpou-se o armazenamento local do navegador com `localStorage.clear()`.

### Prevenção de Falhas em Lote com o Observer Global

**Cenário**: O feedback técnico apontou que observers registrados sob `etc/adminhtml/events.xml` não disparam durante persistências assíncronas ou execuções que fujam do ciclo tradicional de formulário web.

**Solução**: A migração prévia do evento `catalog_product_save_after` para a raiz `etc/events.xml` garantiu que qualquer persistência decorrente de ações em massa opere universalmente sem bloqueios de escopo de área.

### Consumo Mandatório de Service Contracts em Ações em Massa

**Cenário**: É prática desaconselhada injetar a Collection diretamente no controller e executar chamadas destrutivas no banco de dados via `$collection->walk('delete')` ou chamadas diretas a `$model->save()`, violando o desacoplamento de camadas.

**Solução**: Tanto o `MassApprove` quanto o `MassDelete` iteram sobre a coleção resolvida pelo `Filter` e delegam a gravação e remoção exclusivamente ao `AvaliacaoRepositoryInterface` (`save()` e `delete()`), garantindo conformidade com o padrão de Service Contracts, tratamento defensivo e disparo de interceptores.

---

## 6. Engenharia de Permissões e Segurança Administrativa (ACL & ADMIN_RESOURCE)

A governança de segurança corporativa exige blindagem contra acessos indevidos e isolamento estrito de privilégios. No Magento 2, essa disciplina é governada pelo subsistema de ACL (Access Control Lists).

```plaintext
                                [ Magento_Backend::admin ]
                                             │
                                             ▼
                             [ Webjump_Jhonatan::webjump ]
                                             │
                                             ▼
                            [ Webjump_Jhonatan::avaliacao ]  <-- Visualização e Moderação
                                             │
                                             ▼
                         [ Webjump_Jhonatan::avaliacao_export ]  <-- Permissão Restrita (Desafio 15.2)
```

### Por que a constante ADMIN_RESOURCE é mandatória em cada Controller?

O framework Magento intercepta toda requisição administrativa através do plugin `Magento\Backend\App\Action\Plugin\Authentication`. Caso um controller não declare a constante `ADMIN_RESOURCE` apontando para um recurso válido do `acl.xml`, a classe herda o valor padrão nulo, o que pode abrir a tela para qualquer usuário autenticado no painel, inclusive operadores com permissões restritas. No módulo `Webjump_Jhonatan`, todos os controladores (`Index`, `MassApprove`, `MassDelete`) definem explicitamente:

```php
public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao';
```

### Segregação Granular da Permissão de Exportação

Em operações de e-commerce, a extração de bases de clientes e cadastros através de planilhas CSV/Excel configura risco direto de segurança da informação e privacidade. Por essa razão arquitetural, o arquivo `etc/acl.xml` isolou o recurso `Webjump_Jhonatan::avaliacao_export` como um nó filho independente:

- **Operador de Moderação**: Recebe acesso apenas ao nó `Webjump_Jhonatan::avaliacao`, conseguindo visualizar o grid, filtrar avaliações e aprovar registros.
- **Liderança / Gestão**: Recebe o nó filho `Webjump_Jhonatan::avaliacao_export`, habilitando as ferramentas de download de relatórios desenvolvidas na esteira da Semana 15.

---

## 7. Decisões de Engenharia Backend: UI Components, Data Provider e Mass Actions

### Por que adotar UI Components XML em vez de Blocos Legados (Backend\Block\Widget\Grid)?

O padrão de UI Components separa a definição de interface da camada de renderização. O backend fornece um payload JSON normalizado através do endpoint `mui/index/render`, enquanto o motor Knockout.js no navegador renderiza a tabela dinamicamente. Isso reduz o consumo de memória no servidor PHP-FPM, acelera o tempo de resposta e viabiliza a persistência de colunas customizadas por usuário através de bookmarks.

### Por que utilizar SearchResult como coleção do Grid?

A classe `Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult` implementa `SearchResultInterface` e foi concebida para receber os parâmetros de requisição assíncrona enviados pelo DataProvider. Ela intercepta os filtros de data, texto e faixa numérica, aplicando as cláusulas SQL diretamente via adaptador PDO sem necessidade de queries manuais.

### Por que incluir o nó `<confirm>` nas Mass Actions destrutivas?

Ações de exclusão em massa executadas acidentalmente provocam perda irrecuperável de dados em produção. A declaração de `<confirm>` no XML aciona a modal de confirmação nativa do Magento via componente JavaScript `confirmPopup`, exigindo validação explícita do operador antes do envio do formulário.

---

## 8. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as 15 evidências formais de homologação, auditando a infraestrutura de código, navegação no admin, filtros analíticos, ordenação, ações em massa e o teste de bloqueio de segurança com perfil restrito:

### Evidência 01 — Declaração de Rotas e Árvore ACL com Permissão Segregada

**Análise Visual/Técnica**: Leitura limpa dos manifestos `etc/adminhtml/routes.xml` e `etc/acl.xml` via terminal. Comprova o registro do frontName `webjump_avaliacao` no roteador administrativo e a árvore hierárquica da ACL com o recurso `avaliacao_export` isolado como filho de `avaliacao`.

![Evidência 01](https://github.com/user-attachments/assets/8087f461-b2f2-4e99-a435-57c01949d19c)

### Evidência 02 — Mapeamento do DataProvider e Coleção do Grid no di.xml

**Análise Visual/Técnica**: Leitura do arquivo `etc/di.xml` no terminal. Exibe as diretivas `<preference>` dos Service Contracts e a injeção do tipo `UiComponent\DataProvider\CollectionFactory`, mapeando a fonte `webjump_avaliacao_listing_data_source` para a classe especializada `Webjump\Jhonatan\Model\ResourceModel\Avaliacao\Grid\Collection`.

![Evidência 02](https://github.com/user-attachments/assets/49ee5800-3612-44a4-8bf7-9a8eebadf219)

### Evidência 03 — Controller Index com Guarda Rígida de ADMIN_RESOURCE

**Análise Visual/Técnica**: Leitura de `Controller/Adminhtml/Avaliacao/Index.php` via terminal. Comprova a declaração formal da constante pública `public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao';`, o uso de tipagem estrita e a implementação de `HttpGetActionInterface`.

![Evidência 03](https://github.com/user-attachments/assets/317da382-c5a9-4178-9b96-100e471c6cd9)

### Evidência 04 — Menu Administrativo Hierárquico no Painel

**Análise Visual/Técnica**: Painel administrativo (https://magento.test/admin). O menu lateral esquerdo evidencia o grupo de primeiro nível Webjump expandido e o submenu Avaliações ativo com URL de destino apontando para `webjump_avaliacao/avaliacao/index`.

![Evidência 04](https://github.com/user-attachments/assets/f56a66e0-f00b-4016-bff6-20be884339a2)

### Evidência 05 — Carga Inicial do Grid com Dados da Collection

**Análise Visual/Técnica**: Listagem completa em `/admin/webjump_avaliacao/avaliacao/index`. Atesta o carregamento dos 5 registros semeados no banco de dados pelo patch da Semana 14, exibindo o título "Avaliações de Produtos", o botão primário "Nova Avaliação" e todas as 7 colunas estruturadas (ID, ID do Produto, Autor, Comentário, Nota, Status, Criado em).

![Evidência 05](https://github.com/user-attachments/assets/c0c201cd-72d9-4647-aec9-14aec7bab633)

### Evidência 06 — Source Model de Moderação: Rótulos Amigáveis Sim/Não

**Análise Visual/Técnica**: Detalhe das linhas da coluna Status no grid. Comprova a atuação da source model `Magento\Config\Model\Config\Source\Yesno`, traduzindo os valores binários do MariaDB (1 e 0) para rótulos legíveis "Yes" e "No".

![Evidência 06](https://github.com/user-attachments/assets/1e059dc6-a0ee-4eed-a44a-f064eb99fd63)

### Evidência 07 — Filtro Textual Dinâmico por Autor

**Análise Visual/Técnica**: Barra de ferramentas do grid com o painel de filtros expandido. Campo Autor filtrado pelo termo "Lucas", retornando exclusivamente a avaliação de Lucas Ferreira com a etiqueta de filtro ativo visível no topo da tabela.

![Evidência 07](https://github.com/user-attachments/assets/f7bc1e35-4563-49a3-85f2-edb51bfcb452)

### Evidência 08 — Filtro Numérico por Intervalo (Faixa de Nota)

**Análise Visual/Técnica**: Aplicação de filtro `textRange` na coluna Nota com intervalo configurado de 5 a 5. O grid isola os registros de nota máxima (Lucas Ferreira, Mariana Silveira e Beatriz Ramos), comprovando a filtragem numérica da coleção.

![Evidência 08](https://github.com/user-attachments/assets/d1649893-e31e-4dcf-ae3d-df5c234b1e96)

### Evidência 09 — Filtro Temporal por Intervalo de Datas com Calendário

**Análise Visual/Técnica**: Acionamento do componente nativo de calendário `dateRange` na coluna Criado em. Demonstra a seleção de intervalo cobrindo a data de provisionamento com o date picker ativo (09/1/2026) e o retorno exato das linhas filtradas.

![Evidência 09](https://github.com/user-attachments/assets/56f9d8a2-c105-4775-9c5d-a0c023c156ee)

### Evidência 10 — Ordenação Multicolunas e Controle de Paginação

**Análise Visual/Técnica**: Clique no cabeçalho da coluna ID invertendo a ordenação (exibindo a seta indicadora de asc/desc) e destaque para a barra inferior demonstrando o controle de páginas e totalizadores ("5 records found", "1 of 1 pages").

![Evidência 10](https://github.com/user-attachments/assets/92799117-bc9e-4edf-931b-448f8f5500d7)

### Evidência 11 — Modal Confirmatório Nativo em Ação Destrutiva (Excluir)

**Análise Visual/Técnica**: Seleção de item no grid e acionamento da opção "Excluir" no menu de ações em massa. Demonstra a interceptação pelo modal nativo com o texto de segurança: "Tem certeza de que deseja excluir as avaliações selecionadas? (1 record)" e os botões Cancel e OK.

![Evidência 11](https://github.com/user-attachments/assets/d0c0a0f0-f341-43cd-8c86-a343d59d2f82)

### Evidência 12 — Ação em Massa: Aprovar Registros com Notificação de Sucesso

**Análise Visual/Técnica**: Seleção das avaliações pendentes com status "No" (IDs 4 e 5) e disparo da ação "Aprovar". Exibe a notificação verde de sucesso no topo ("Total de 2 avaliação(ões) aprovada(s) com sucesso.") e a coluna Status atualizada para "Yes" em todas as linhas da tabela.

![Evidência 12](https://github.com/user-attachments/assets/11253785-57c1-4580-985e-3c309d19fee8)

### Evidência 13 — Configuração de Perfil Restrito na Árvore de ACL

**Análise Visual/Técnica**: Painel administrativo em System > Permissions > User Roles na edição do perfil "Atendimento Restrito". Comprova a árvore de recursos com as caixas de seleção Webjump, Avaliações e Exportar Avaliações explicitamente desmarcadas, preparando a validação de segurança.

![Evidência 13](https://github.com/user-attachments/assets/75e354f2-d524-495f-837f-ee40062f6538)

### Evidência 14 — Auditoria de Segurança: Bloqueio de Acesso ao Usuário Restrito

**Análise Visual/Técnica**: Sessão administrativa autenticada com o usuário restrito (`operador_teste`) em janela anônima. Tentativa de acesso direto à URL `/admin/webjump_avaliacao/avaliacao/index` resultando no redirecionamento para a tela `webjump_avaliacao/denied/index`, com a mensagem "Sorry, you need permissions to view this content." e a barra lateral sem o menu Webjump, validando a eficácia da constante `ADMIN_RESOURCE`.

![Evidência 14](https://github.com/user-attachments/assets/3b2ac9dd-8ec2-45de-8214-f74209b42856)

### Evidência 15 — Integridade de Compilação e Inviolabilidade do Core (CLI)

**Análise Visual/Técnica**: Terminal do WSL2 executando `bin/magento setup:di:compile && git status`. Evidencia a compilação finalizada em 100% com sucesso (35 segundos) e o controle de versão atestando que todas as alterações estão estritamente contidas em `app/code/Webjump/Jhonatan/`, preservando o diretório `vendor/` 100% intocado.

![Evidência 15](https://github.com/user-attachments/assets/4a1b501b-ceb4-4443-a2a7-d4810e6ba99f)
```