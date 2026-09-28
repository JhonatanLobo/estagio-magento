# Implementação Técnica Magento 2: Formulário UI Component, Configuração de Sistema e Exportação

## 1. Visão Geral do Entregável

Este repositório e Pull Request formalizam a entrega técnica do Desafio 15.2 (Formulário, configuração e exportação) da Sprint 7 (Magento 2 / Adobe Commerce). O objetivo central consistiu em fechar o ciclo operacional do CRUD para a entidade de avaliações de produtos (`webjump_avaliacao`), dotando o painel administrativo de interface para criação e edição com validação reativa, governança de configurações globais em **Stores > Configuration** com valores padrão (defaults), tratamento defensivo de erros com retenção de formulário via `DataPersistorInterface`, e mecanismos nativos de exportação tabular (CSV e Excel XML) que respeitam dinamicamente os filtros aplicados no grid, assegurando a inviolabilidade estrita do diretório `vendor/`.

A implementação apoia-se em cinco pilares fundamentais de engenharia de software corporativa:

- **Formulário Declarativo e Reativo (UI Components Form):** Construção da interface de criação e edição através de `webjump_avaliacao_form.xml`, integrando o componente JavaScript `Magento_Ui/js/form/form` a um DataProvider especializado, dispensando blocos PHP legados e assegurando a renderização assíncrona orientada a fieldsets.
- **Validação Estrita de Dados (Client-Side Validation):** Implementação de regras de validação declarativas (`required-entry` e `validate-digits`) nos campos do formulário, impedindo submissões inconsistentes antes do envio da requisição HTTP ao servidor.
- **Ciclo CRUD Desacoplado via Service Contracts com Tratamento de Erro:** Os controladores administrativos (`NewAction`, `Edit`, `Save` e `Delete`) realizam mutações de estado e consultas de domínio estritamente através do `AvaliacaoRepositoryInterface`. O `Save.php` valida a existência real do produto no catálogo via `ProductRepositoryInterface`, tratando exceções de negócio (`LocalizedException`, `NoSuchEntityException`) e preservando os dados digitados na sessão através do `DataPersistorInterface`.
- **Governança de Configurações no Admin (system.xml e config.xml):** Disponibilização de opções modulares em **Stores > Configuration** sob a aba unificada Webjump, declarando valores padrão para ativação do módulo e nota mínima de corte, além de condicionar de forma defensiva a renderização do selo de sustentabilidade na vitrine pública (PDP).
- **Mecanismo de Exportação Tabular com Respeito a Filtros:** Acoplamento do nó `<exportButton>` na barra de ferramentas do grid, viabilizando o download assíncrono em formatos CSV e Excel XML que refletem fidedignamente o estado filtrado da coleção em tempo real.

---

## 2. Arquitetura e Organização de Arquivos do Projeto

Abaixo é apresentada a árvore completa de artefatos estruturados no módulo `src/app/code/Webjump/Jhonatan/`, evidenciando a separação estrita entre apresentação, persistência, controladores e configurações:

```plaintext
src/app/code/Webjump/Jhonatan/
├── registration.php                                                # Registro do componente no ComponentRegistrar
├── etc/
│   ├── module.xml                                                  # Declaração do módulo e dependências
│   ├── events.xml                                                  # Observer global (catalog_product_save_after)
│   ├── di.xml                                                      # Mapeamento de Service Contracts e DataProvider
│   ├── acl.xml                                                     # Árvore de permissões e governança de acesso
│   ├── db_schema.xml                                               # Definição declarativa da tabela webjump_avaliacao
│   ├── db_schema_whitelist.json                                    # Whitelist de integridade relacional
│   ├── config.xml                                                  # Valores padrão unificados (Home e Avaliações)
│   └── adminhtml/
│       ├── routes.xml                                              # Mapeamento de rotas administrativas
│       ├── menu.xml                                                # Menus Webjump > Avaliações
│       └── system.xml                                              # Configurações em Stores > Configuration
│
├── Api/
│   ├── AvaliacaoRepositoryInterface.php                            # Service Contract de CRUD e busca
│   └── Data/
│       ├── AvaliacaoInterface.php                                  # Contrato de dados da entidade (getters/setters)
│       └── AvaliacaoSearchResultsInterface.php                     # Contrato para coleções paginadas da API
│
├── Block/
│   └── Adminhtml/
│       └── Avaliacao/
│           └── Edit/
│               ├── GenericButton.php                               # Utilitário base de resolução de IDs e rotas
│               ├── BackButton.php                                  # Botão Voltar para o grid
│               ├── DeleteButton.php                                # Botão Excluir com confirmação nativa
│               ├── SaveButton.php                                  # Botão primário Salvar Avaliação
│               └── SaveAndContinueButton.php                       # Botão Salvar e Continuar Edição
│
├── Controller/
│   └── Adminhtml/
│       └── Avaliacao/
│           ├── Index.php                                           # Controller principal da grade de listagem
│           ├── NewAction.php                                       # Encaminhamento para tela de criação
│           ├── Edit.php                                            # Carregamento e inicialização do formulário
│           ├── Save.php                                            # Persistência via repositório com DataPersistor e checagem de catálogo
│           ├── Delete.php                                          # Exclusão individual via deleteById()
│           ├── MassApprove.php                                     # Aprovação em lote via Service Contract
│           └── MassDelete.php                                      # Exclusão em massa com confirmação
│
├── Model/
│   ├── Avaliacao.php                                               # Model da entidade (Active Record / Negócio)
│   ├── AvaliacaoRepository.php                                     # Implementação concreta do Service Contract
│   ├── AvaliacaoSearchResults.php                                  # Implementação concreta dos resultados de busca
│   ├── Avaliacao/
│   │   └── DataProvider.php                                        # DataProvider do formulário UI Component
│   ├── Config/
│   │   └── Source/
│   │       └── Nota.php                                            # Source model de seleção de notas (1 a 5)
│   └── ResourceModel/
│       ├── Avaliacao.php                                           # ResourceModel mapeando tabela e chave primária
│       └── Avaliacao/
│           ├── Collection.php                                      # Coleção relacional de dados
│           └── Grid/
│               └── Collection.php                                  # Coleção SearchResult especializada para o grid
│
├── Ui/
│   └── Component/
│       └── Listing/
│           └── Column/
│               └── AvaliacaoActions.php                            # Construtor dinâmico dos links Editar/Excluir
│
├── ViewModel/
│   ├── HomeBlock.php                                               # Resolução de dados do bloco da Home
│   └── ProductBadge.php                                            # Resolução defensiva do selo com ScopeConfig
│
└── view/
    ├── adminhtml/
    │   ├── layout/
    │   │   ├── webjump_avaliacao_avaliacao_index.xml              # Injeção do UI Component da listagem
    │   │   ├── webjump_avaliacao_avaliacao_edit.xml               # Injeção do UI Component do formulário
    │   │   └── webjump_avaliacao_avaliacao_new.xml                # Herança declarativa do layout de edição
    │   └── ui_component/
    │       ├── webjump_avaliacao_listing.xml                       # Grid com exportButton e actionsColumn
    │       └── webjump_avaliacao_form.xml                          # Formulário UI Component com validação
    └── frontend/
        ├── layout/
        │   └── catalog_product_view.xml                            # Layout da PDP com o selo sustentável
        ├── templates/
        │   └── product/
        │       └── badge.phtml                                     # Template semântico e defensivo da vitrine
        └── web/
            └── css/
                └── product-badge.css                               # Estilização modular e responsiva do selo
```

---

## 3. Rastreabilidade dos Critérios de Aceite

A tabela abaixo correlaciona os requisitos avaliativos da especificação do Desafio 15.2 com as soluções técnicas desenvolvidas e as respectivas evidências comprobatórias de QA:

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
|---|---|---|---|
| Criar e editar pelo admin funciona, com validação de campo obrigatório | Implementação de `webjump_avaliacao_form.xml` alimentado por DataProvider, botões de ação e regras `required-entry` e `validate-digits`. | Concluído | Evidências 03, 04, 06 e 07 |
| Save usa o repository | Controlador `Save.php` delegando a persistência ao `AvaliacaoRepositoryInterface->save()`. | Concluído | Evidência 05 |
| Save trata erro devolvendo mensagem ao usuário | `Save.php` valida a existência do produto via `ProductRepositoryInterface`, captura exceções com `messageManager->addErrorMessage()` e retém os dados digitados via `DataPersistorInterface`. | Concluído | Evidência 16 |
| Exclusão com Diálogo Confirmatório | Implementação de `DeleteButton` acionando modal nativa `deleteConfirm()` e controller `Delete.php` consumindo repositório. | Concluído | Evidências 08 e 09 |
| Seção em Stores > Configuration com valores padrão funcionando | Declaração da seção `webjump_avaliacao` em `system.xml` com valores padrão (`ativo = 1`, `nota_minima = 3`) definidos em `config.xml` sob `<default>`. | Concluído | Evidência 10 |
| Módulo respeita a configuração (desabilitado → some da loja) | `ProductBadge` injetando `ScopeConfigInterface` defensivamente: supressão total do selo na PDP quando o módulo está desativado. | Concluído | Evidências 11 e 12 |
| Exportação em CSV e Excel XML funciona | Habilitação do componente `<exportButton>` na toolbar do grid mapeando endpoints `mui/export/gridToCsv` e `gridToXml` com arquivos abertos em planilha. | Concluído | Evidências 01, 13 e 17 |
| Exportação respeita os filtros aplicados no grid | O motor de exportação do Magento consome a coleção filtrada pelo DataProvider, exportando estritamente os registros filtrados na tela (comprovado por print). | Concluído | Evidência 14 |
| Integridade de Build e Inviolabilidade do Core | Compilação em 100% via `setup:di:compile` e preservação absoluta do diretório `vendor/` intocado. | Concluído | Evidência 15 |

---

## 4. Passo a Passo do Processo de Desenvolvimento

A esteira de construção técnica seguiu a separação em camadas recomendada pela Adobe, evitando atalhos arquiteturais e garantindo total conformidade de código:

### Passo 1: Modelagem de Configuração de Sistema (system.xml e config.xml)

- Estrutureou-se o arquivo `etc/adminhtml/system.xml` preservando a seção legada da Home e introduzindo a nova seção `webjump_avaliacao` com `sortOrder="20"`.
- Declararam-se os campos `ativo` (booleano com Yesno source model) e `nota_minima` (com regra de validação `validate-digits`).
- No arquivo `etc/config.xml`, unificaram-se os nós `<default>`, assegurando que o módulo inicialize operacional (`ativo = 1` e `nota_minima = 3`) antes de qualquer intervenção manual no banco de dados.

### Passo 2: Extensão do ProductBadge para Respeito à Configuração

- Refatorou-se o ViewModel `ViewModel/ProductBadge.php` para injetar `Magento\Framework\App\Config\ScopeConfigInterface`.
- Criou-se o método auxiliar `isModuleEnabled()` que inspeciona a flag `webjump_avaliacao/geral/ativo` no escopo `SCOPE_STORE`.
- No método `isSustentavel()`, adicionou-se uma guarda defensiva preliminar: se o módulo estiver desativado, o método aborta imediatamente e retorna falso, suprimindo o selo da vitrine sem deixar nós vazios no DOM.

### Passo 3: Coluna de Ações e Botão de Exportação no Grid

- Criou-se a classe `Ui/Component/Listing/Column/AvaliacaoActions.php` estendendo `Magento\Ui\Component\Listing\Columns\Column` para compor os links de edição e exclusão de cada linha.
- Atualizou-se o arquivo `view/adminhtml/ui_component/webjump_avaliacao_listing.xml` injetando o `<exportButton>` dentro de `listingToolbar` com opções para CSV e Excel XML.
- Registrou-se a tag `<actionsColumn name="actions">` associada à classe `AvaliacaoActions`, habilitando as ações individuais na extremidade direita da tabela.

### Passo 4: DataProvider e Suporte aos Botões do Formulário

- Desenvolveu-se `Model/Config/Source/Nota.php` implementando `OptionSourceInterface` para alimentar o dropdown de notas (1 a 5 Estrelas).
- Construiu-se a arquitetura de botões sob `Block/Adminhtml/Avaliacao/Edit/` (GenericButton, BackButton, DeleteButton, SaveButton, SaveAndContinueButton), garantindo injeção de atributos de formulário e chamadas à função nativa `deleteConfirm()`.
- Implementou-se `Model/Avaliacao/DataProvider.php` estendendo `AbstractDataProvider` com a propriedade tipada via PHPDoc e integração com `DataPersistorInterface` para retenção de dados em caso de falha de validação.

### Passo 5: Declaração Reativa do UI Component Form

- Desenvolveu-se `view/adminhtml/ui_component/webjump_avaliacao_form.xml` contendo os fieldsets e campos (`avaliacao_id`, `product_id`, `autor`, `nota`, `aprovado`, `comentario`).
- Aplicaram-se as regras de validação client-side `<rule name="required-entry" xsi:type="boolean">true</rule>` e `<rule name="validate-digits" xsi:type="boolean">true</rule>`.
- Criaram-se os layouts `view/adminhtml/layout/webjump_avaliacao_avaliacao_edit.xml` (injetando o formulário em `content`) e `webjump_avaliacao_avaliacao_new.xml` (reaproveitando a definição via `<update handle="..."/>`).

### Passo 6: Controladores CRUD via Service Contracts e Tratamento de Erro

- Implementou-se `Controller/Adminhtml/Avaliacao/NewAction.php` utilizando `ForwardFactory` para encaminhar a requisição para a action `edit` com proteção de `ADMIN_RESOURCE`.
- Construiu-se `Controller/Adminhtml/Avaliacao/Edit.php` validando a existência do registro através de `AvaliacaoRepositoryInterface->getById()` com tratamento para `NoSuchEntityException`.
- Construiu-se `Controller/Adminhtml/Avaliacao/Save.php` consumindo `AvaliacaoRepositoryInterface->save()`, `AvaliacaoFactory` e `ProductRepositoryInterface`, persistindo novas avaliações, atualizando registros de forma idempotente, capturando erros de produto inexistente e retendo os dados via DataPersistor.
- Construiu-se `Controller/Adminhtml/Avaliacao/Delete.php` consumindo `AvaliacaoRepositoryInterface->deleteById()` com mensagens sanitizadas de sucesso e erro.

---

## 5. Decisões Arquiteturais e Resolução de Problemas (Troubleshooting)

### O Problema: Incompatibilidade de Tipagem na Propriedade Herdada `$collection` (TypeError)

**Sintoma:** Ao acessar a rota de criação (`/admin/webjump_avaliacao/avaliacao/new`), a aplicação abortou com erro fatal:

```plaintext
Fatal error: Type of Webjump\Jhonatan\Model\Avaliacao\DataProvider::$collection must not be defined
(as in class Magento\Ui\DataProvider\AbstractDataProvider) in /var/www/html/.../DataProvider.php on line 11
```

**Causa Raiz:** A classe base do core `Magento\Ui\DataProvider\AbstractDataProvider` declara a propriedade `protected $collection;` sem tipagem estrita do PHP. No PHP 7.4/8.x, uma classe filha não pode impor tipagem de classe (`protected Collection $collection;`) sobre uma propriedade não tipada na classe pai herdada.

**A Solução:** Removeu-se a declaração estrita de tipo na propriedade do `DataProvider.php`, mantendo a anotação formal via bloco PHPDoc (`/** @var Collection */ protected $collection;`), satisfazendo as regras de herança do PHP e preservando a integridade da análise estática.

### O Problema: Erro de Validação de Schema XML (requestFieldName)

**Sintoma:** Lançamento de `LocalizedException`: `The XML in file .../webjump_avaliacao_form.xml is invalid: Element 'requestFieldName': This element is not expected. Line: 35.`

**Causa Raiz:** As tags `<requestFieldName>` e `<primaryFieldName>` foram declaradas erroneamente dentro do container `<dataSource><settings>`. De acordo com o schema `ui_configuration.xsd`, essas tags pertencem exclusivamente ao nó filho `<dataProvider><settings>`.

**A Solução:** Ajustou-se a topologia do XML, mantendo apenas `<submitUrl>` sob `<dataSource><settings>` e restringindo `<requestFieldName>` e `<primaryFieldName>` ao bloco do dataProvider.

### O Problema: Carregamento Infinito por Incompatibilidade de Template na Raiz do Formulário

**Sintoma:** O formulário administrativo exibia a máscara de carregamento cinza (loading spinner) indefinidamente com a mensagem "0 records found" e sem erros no console do navegador.

**Causa Raiz:** A raiz `<form>` estende `uiCollection` e não possui um arquivo de template próprio. O componente JavaScript nativo (`Magento_Ui/js/form/form`) necessita herdar o provider explicitamente no nó de configuração (`js_config/provider`) para se ligar ao dataSource e permitir que o Knockout.js desative o indicador de carregamento.

**A Solução:** Declarou-se explicitamente `<item name="provider" xsi:type="string">webjump_avaliacao_form.avaliacao_form_data_source</item>` dentro do `js_config` da tag `<form>`, garantindo a resolução síncrona dos nós no `uiRegistry` do Magento.

### O Problema: Dessincronização de Estado na Vitrine (Configuração vs. Atributo EAV)

**Sintoma:** Ao reativar o módulo de avaliações em **Stores > Configuration**, o selo "Produto Sustentável" não voltou a renderizar na página do produto (PDP).

**Causa Raiz:** O método `ProductBadge::isSustentavel()` opera sob uma condição lógica conjuntiva (AND): ele valida se a configuração global está ligada (`isModuleEnabled()`) E se o produto específico possui o atributo EAV ativo (`selo_sustentavel == 1`). Durante os testes defensivos do Desafio 14.1, o produto `MOUSE-PRO-01` fora alternado para `0` no banco MariaDB. Adicionalmente, o cache Full Page (FPC) mantinha a versão estática suprimida em memória.

**A Solução:** Restabeleceu-se o valor `1` na tabela `catalog_product_entity_int` para o produto de teste, e executou-se a purga completa do cache (`bin/magento cache:clean full_page block_html config`), restabelecendo instantaneamente a exibição do selo verde na vitrine.

---

## 6. Engenharia de Formulários, DataProvider e Service Contracts

**Por que utilizar UI Components Form em vez de blocos legados (Widget\Form\Container)?**
O ecossistema moderno da Adobe desacopla a definição estrutural da apresentação visual. O backend limita-se a injetar os metadados e os valores no formato JSON através do DataProvider, enquanto o Knockout.js gerencia as mutações de estado e validações reativas no cliente sem recarregamento de página.

**Qual o papel do DataPersistorInterface?**
Em caso de exceções no salvamento (ex.: produto inexistente ou erro relacional), redirecionar o usuário de volta para o formulário causaria a perda de todo o texto digitado. O DataPersistor armazena o array POST na sessão do usuário temporariamente; quando o DataProvider recarrega a tela, ele recupera os dados da sessão, popula os campos e limpa a persistência.

**Por que utilizar AvaliacaoRepositoryInterface em vez de chamar `$model->save()`?**
Chamar métodos diretos do Model acopla os controladores à camada de infraestrutura e ao ORM (AbstractModel). Ao mediar pelo Service Contract, respeitam-se os contratos de serviço da plataforma, permitindo que interceptores (plugins), observadores e esteiras transacionais atuem de forma uniforme.

---

## 7. Governança de Configuração e Exportação de Dados

**Isolamento de Escopo com ScopeConfigInterface:** O uso da chave `webjump_avaliacao/geral/ativo` permite ao lojista desativar os recursos públicos e selos do módulo sem necessidade de intervenção em código ou indisponibilidade da loja. Ao consultar o escopo `SCOPE_STORE`, o sistema respeita a autonomia comercial de cada Store View da instalação.

**Mecanismo de Streaming de Exportação:** Diferente de rotinas customizadas que carregam milhares de linhas na memória do PHP gerando estouro de `memory_limit`, o `<exportButton>` consome os endpoints nativos do Magento (`gridToCsv` e `gridToXml`). O motor consome a coleção filtrada pelo DataProvider e descarrega os dados em lotes via stream output, garantindo alta performance mesmo sob grandes volumes de dados.

---

## 8. Relatório Detalhado de Quality Assurance (QA)

Abaixo apresentam-se as 17 evidências formais de homologação, auditando todo o ciclo de vida do CRUD, a validação de formulários, o tratamento defensivo de erros no Save com DataPersistor, a configuração global, a vitrine pública e a exportação em CSV e Excel XML respeitando filtros:

### Evidência 01 — ExportButton no Grid Administrativo

**Análise Visual/Técnica:** Listagem administrativa em `/admin/webjump_avaliacao/avaliacao/index` exibindo o menu suspenso do botão Export expandido na toolbar, comprovando as opções nativas CSV e Excel XML integradas ao grid.

<img width="1915" height="1027" alt="Image" src="https://github.com/user-attachments/assets/77faca62-5ee2-43f4-9e77-c7a909c7bd2b" />

### Evidência 02 — Coluna de Ações com Links de Edição e Exclusão

**Análise Visual/Técnica:** Destaque para a extremidade direita do grid evidenciando o menu Select aberto da coluna Ações, comprovando a atuação da classe `AvaliacaoActions` fornecendo os links funcionais de Editar e Excluir.

<img width="1915" height="1026" alt="Image" src="https://github.com/user-attachments/assets/8dcaa8df-68c3-4baa-84de-7d0fe8126598" />

### Evidência 03 — Formulário de Nova Avaliação em Branco

**Análise Visual/Técnica:** Acesso à rota `/admin/webjump_avaliacao/avaliacao/new`. Comprova a resolução do layout handler herdado, exibindo o título "Nova Avaliação", botões de topo e todos os campos limpos (ID do Produto, Nome do Autor, Nota, Status, Comentário).

<img width="1916" height="1027" alt="Image" src="https://github.com/user-attachments/assets/9e326f69-0256-4655-adb2-4f8d5d1827b0" />

### Evidência 04 — Validação Client-Side de Campos Obrigatórios

**Análise Visual/Técnica:** Tentativa de submissão do formulário em branco ao clicar em "Salvar Avaliação". Demonstra o bloqueio imediato do envio com os avisos textuais em vermelho (This is a required field.) sob os campos obrigatórios.

<img width="1912" height="1026" alt="Image" src="https://github.com/user-attachments/assets/7be3db41-d841-4fed-9982-967d958faf18" />

### Evidência 05 — Persistência de Nova Avaliação via Repositório

**Análise Visual/Técnica:** Redirecionamento para o grid após cadastro com preenchimento completo. Comprova a notificação verde de confirmação ("Avaliação gravada com sucesso.") e a inclusão da nova linha persistida no MariaDB sob o ID 6 (Amanda Borges).

<img width="1912" height="1025" alt="Image" src="https://github.com/user-attachments/assets/7032bc3a-3585-46ab-aabb-5a9cb922783f" />

### Evidência 06 — Formulário de Edição Carregado pelo DataProvider

**Análise Visual/Técnica:** Abertura da rota de edição `/admin/webjump_avaliacao/avaliacao/edit/avaliacao_id/4`. Comprova a hidratação dos dados pelo DataProvider com as informações da autora Beatriz Ramos e a presença do botão "Excluir Avaliação" na barra de topo.

<img width="1916" height="1027" alt="Image" src="https://github.com/user-attachments/assets/2656e66e-f290-4b15-bb73-ed409c35f831" />

### Evidência 07 — Mutação de Estado com Ação "Salvar e Continuar"

**Análise Visual/Técnica:** Alteração da nota para 4 Estrelas e status para Yes na avaliação #4, seguida de clique em "Salvar e Continuar". Demonstra a permanência na tela de edição, a mensagem de sucesso e a atualização dos dados em memória e banco.

<img width="1917" height="1026" alt="Image" src="https://github.com/user-attachments/assets/d768606c-ce78-4dbd-aaee-6cac95aaf5e0" />

### Evidência 08 — Modal de Confirmação em Exclusão Individual

**Análise Visual/Técnica:** Acionamento do botão "Excluir Avaliação" no formulário de edição. Demonstra a abertura da janela modal nativa do Magento com a mensagem de segurança: "Tem certeza de que deseja excluir esta avaliação?" e os botões Cancel e OK.

<img width="1916" height="1026" alt="Image" src="https://github.com/user-attachments/assets/5eb1e549-000e-426e-b13c-4eceedbaef57" />

### Evidência 09 — Exclusão Individual Concluída via Repositório

**Análise Visual/Técnica:** Retorno ao grid após confirmação de exclusão da avaliação #6. Exibe o banner verde de sucesso ("A avaliação foi excluída com sucesso.") e a contagem total de registros retornando a 5 na grade.

<img width="1916" height="1027" alt="Image" src="https://github.com/user-attachments/assets/dc677bfb-e6ee-4160-9017-b92600acbca6" />

### Evidência 10 — Seção de Configuração com Valores Padrão (system.xml & config.xml)

**Análise Visual/Técnica:** Interface em **Stores > Configuration > Webjump > Avaliações de Produtos**. Comprova os campos "Habilitar Módulo de Avaliações" e "Nota Mínima para Exibição Pública" exibindo os valores padrão Yes e 3 herdados do `config.xml` sob o escopo `[store view]`.

<img width="1917" height="1027" alt="Image" src="https://github.com/user-attachments/assets/4767dd53-7ffc-470a-86bd-6784005e38d8" />

### Evidência 11 — Respeito à Configuração: Módulo Desabilitado na Vitrine

**Análise Visual/Técnica:** Página do produto (`/mouse-gamer-pro-wireless.html`) com a configuração global desativada (`ativo = No`). A inspeção do DOM via DevTools atesta que o badge verde "Produto Sustentável" foi suprimido sem deixar containers órfãos.

<img width="1917" height="1026" alt="Image" src="https://github.com/user-attachments/assets/58834ef1-398c-4c79-a8f6-847aa7ecc42a" />

### Evidência 12 — Respeito à Configuração: Módulo Reabilitado na Vitrine

**Análise Visual/Técnica:** Reativação da configuração (`ativo = Yes`) e purga de cache. Comprova a restauração imediata do selo institucional verde de folha posicionado harmoniosamente acima do preço na vitrine da loja.

<img width="1916" height="1026" alt="Image" src="https://github.com/user-attachments/assets/d11a4add-ae46-4659-a3ea-0ff3f192cd8f" />

### Evidência 13 — Exportação Integral em Formato CSV

**Análise Visual/Técnica:** Arquivo `export.csv` aberto no LibreOffice Calc gerado a partir do grid sem filtros. Demonstra as 7 colunas completas (ID, ID do Produto, Autor, Comentário, Nota, Status, Criado em) e os 5 registros tabulados sem quebra de caracteres.

<img width="1615" height="375" alt="Image" src="https://github.com/user-attachments/assets/eafc2297-2169-498b-a99d-88fe7d3d0f75" />

### Evidência 14 — Exportação Respeitando Filtros Ativos do Grid

**Análise Visual/Técnica:** Enquadramento duplo comprovando o atendimento ao critério mais crítico de exportação. No topo, o grid administrativo filtrado pelo termo Autor: Lucas (retornando 1 registro); abaixo, o arquivo CSV baixado contendo estritamente a linha filtrada correspondente ao autor Lucas Ferreira.

<img width="1041" height="746" alt="Image" src="https://github.com/user-attachments/assets/c97ff389-35ac-44b3-97bd-92ff71dc8aa2" />

### Evidência 15 — Integridade de Compilação e Inviolabilidade do Core (CLI)

**Análise Visual/Técnica:** Terminal do WSL2 executando `bin/magento setup:di:compile && git status`. Comprova a compilação finalizada em 100% com sucesso (30 segundos) e o status do Git atestando que todas as alterações estão estritamente contidas em `src/app/code/Webjump/Jhonatan/`, preservando a pasta `vendor/` 100% intocada.

<img width="1151" height="521" alt="Image" src="https://github.com/user-attachments/assets/f52c46bd-0e31-4ed6-9c0d-0adc350755b3" />

### Evidência 16 — Tratamento Defensivo de Erro no Save (DataPersistor)

**Análise Visual/Técnica:** Tentativa de cadastro informando o ID de produto inexistente 9999. Demonstra o tratamento da exceção pelo controller `Save.php` com exibição da notificação vermelha de erro ("O produto com ID '9999' não foi encontrado no catálogo.") e os campos do formulário preenchidos preservados intactos na tela, comprovando na prática a retenção de dados via `DataPersistorInterface`.

<img width="1917" height="1026" alt="Image" src="https://github.com/user-attachments/assets/bae008e6-497d-4ad2-b7b5-fdb69f8987e8" />

### Evidência 17 — Exportação Integral em Formato Excel XML (Planilha Aberta)

**Análise Visual/Técnica:** Arquivo `export.xml` baixado através da opção Excel XML do grid e aberto no LibreOffice Calc. Demonstra a estrutura tabular preservada com cabeçalho completo (ID, ID do Produto, Autor, Comentário, Nota, Status, Criado em) e os registros preenchidos sem corrupção de tags XML.

<img width="1556" height="355" alt="Image" src="https://github.com/user-attachments/assets/d63f29ca-5586-4118-8d2f-a8fecf5886e9" />