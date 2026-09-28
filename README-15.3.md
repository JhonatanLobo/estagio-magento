# Implementação Técnica Magento 2: Exportação Customizada de Grid com Formatação Regional e Governança ACL

## 1. Visão Geral do Entregável

Este repositório e Pull Request formalizam a entrega técnica do Desafio 15.3 (A exportação que o cliente realmente queria) da Sprint 7 (Magento 2 / Adobe Commerce). O objetivo principal consistiu em reestruturar a esteira de exportação da entidade de avaliações (`webjump_avaliacao`), fornecendo uma planilha formatada para o departamento de atendimento ao cliente com o status amigável (Sim ou Não), carimbo de data/hora no padrão brasileiro (DD/MM/AAAA HH:mm:ss) e a resolução dinâmica do Nome do Produto (via `ProductRepositoryInterface`), preservando a integridade absoluta dos grids nativos do core (Pedidos e Clientes) e a integridade da pasta `vendor/` intocada.

A arquitetura se apoia em cinco pilares fundamentais de engenharia de software:

- **Arquitetura de Isolamento (Estratégia B - Zero Blast Radius):** Implementação de controladores e conversores dedicados sob a rota própria do módulo (`webjump_avaliacao/export/*`), contornando a substituição global via `<preference>` sobre classes do core e anulando qualquer risco de regressão em telas transacionais da loja.
- **Resolução Otimizada de Entidades Relacionadas:** Consulta com cache de primeiro nível em memória (`$productNameCache`) para carregar o nome do produto no catálogo sem incorrer em sobrecarga de consultas SQL em lote.
- **Compatibilidade com Excel e Localização Regional:** Injeção do marcador de ordem de byte UTF-8 (BOM `\xEF\xBB\xBF`) na stream do arquivo CSV, assegurando a correta renderização de caracteres acentuados no Microsoft Excel e LibreOffice Calc sem exigir configuração manual do usuário.
- **Segregação Real de Privilégios (Zend_Acl):** Reestruturação do `etc/acl.xml` posicionando `avaliacao` e `avaliacao_export` como recursos irmãos independentes, eliminando a herança implícita de permissões e garantindo o bloqueio efetivo de usuários restritos mesmo via URL direta.
- **Processamento de Dados em Streaming:** Paginação orientada a lotes de 200 registros no consumo da coleção, mantendo o consumo de memória do PHP-FPM estável durante a extração de grandes volumes de dados.

## 2. Arquitetura e Organização de Arquivos do Projeto

```plaintext
src/app/code/Webjump/Jhonatan/
├── registration.php                                                # Registro do componente no ComponentRegistrar
├── etc/
│   ├── module.xml                                                  # Declaração do módulo e sequência de boot
│   ├── events.xml                                                  # Observer global (catalog_product_save_after)
│   ├── di.xml                                                      # Mapeamento de Service Contracts e DataProvider
│   ├── acl.xml                                                     # Árvore de permissões e governança de acesso (ACL)
│   ├── db_schema.xml                                               # Definição física da tabela webjump_avaliacao
│   ├── db_schema_whitelist.json                                    # Whitelist declarativa de segurança da tabela
│   ├── config.xml                                                  # Valores padrão unificados (Home e Avaliações)
│   └── adminhtml/
│       ├── routes.xml                                              # Mapeamento do frontName para o roteador admin
│       ├── menu.xml                                                # Registro dos nós "Webjump > Avaliações" no menu
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
│       ├── Avaliacao/
│       │   ├── Index.php                                           # Controller principal do grid de listagem
│       │   ├── NewAction.php                                       # Encaminhamento para tela de criação
│       │   ├── Edit.php                                            # Inicialização do formulário de edição
│       │   ├── Save.php                                            # Persistência via repositório com DataPersistor
│       │   ├── Delete.php                                          # Exclusão individual via deleteById()
│       │   ├── MassApprove.php                                     # Aprovação em massa via Service Contract
│       │   └── MassDelete.php                                      # Exclusão em massa com confirmação
│       └── Export/
│           ├── GridToCsv.php                                       # Controller de exportação CSV customizado (ACL isolada)
│           └── GridToXml.php                                       # Controller de exportação XML customizado (ACL isolada)
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
│   ├── Export/
│   │   ├── ConvertToCsv.php                                        # Conversor customizado CSV (Data BR, Sim/Não, Produto)
│   │   └── ConvertToXml.php                                        # Conversor customizado XML (Data BR, Sim/Não, Produto)
│   └── ResourceModel/
│       ├── Avaliacao.php                                           # ResourceModel mapeando tabela e chave primária
│       └── Avaliacao/
│           ├── Collection.php                                      # Coleção relacional padrão para seleções em massa
│           └── Grid/
│               └── Collection.php                                  # Coleção especializada SearchResult para o UI Component
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
    │       ├── webjump_avaliacao_listing.xml                       # Grid com exportButton apontando para rotas próprias
    │       └── webjump_avaliacao_form.xml                          # Formulário UI Component com validação
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

## 3. Rastreabilidade dos Critérios de Aceite

| Requisito do Desafio | Solução Técnica Implementada | Status | Evidências de QA |
| --- | --- | --- | --- |
| Status como Sim ou Não | `ConvertToCsv` e `ConvertToXml` mapeiam o campo `aprovado`, convertendo valores booleanos em rótulos de texto "Sim" e "Não". | Concluído | Evidências 02 e 03 |
| Data em Formato Brasileiro | Método de formatação temporal aplicando o formato `d/m/Y H:i:s` via `DateTime` nas datas da entidade. | Concluído | Evidências 02 e 03 |
| Nome do Produto no Arquivo | Injeção de `ProductRepositoryInterface` com cache de execução para associar dinamicamente o nome a partir de `product_id`. | Concluído | Evidências 02 e 03 |
| Exportação Respeitando Filtros | Conversores consomem a coleção filtrada fornecida pela classe `Filter` do UI Component. | Concluído | Evidências 04, 05 e 06 |
| Inviolabilidade dos Grids do Core | Adoção da Estratégia B (controladores independentes), mantendo as exportações nativas de Pedidos e Clientes inalteradas. | Concluído | Evidências 07, 08 e 09 |
| Governança e Teste de ACL | Controladores protegidos por `ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao_export'` e recursos irmãos em `acl.xml`. | Concluído | Evidências 10, 11 e 12 |
| Integridade de Build e Core | Compilação com sucesso total via `setup:di:compile` e preservação estrita do diretório `vendor/` intocado. | Concluído | Evidências 14 e 15 |

## 4. Passo a Passo do Desenvolvimento Técnico

**Desenvolvimento dos Conversores Customizados (Model/Export):**

- Criação de `Model/Export/ConvertToCsv.php` através da injeção de `Filesystem`, `Filter` e `ProductRepositoryInterface`. O método `getCsvFile()` estabelece o arquivo sob `var/export/`, escreve a linha de cabeçalho em português com a coluna 'Nome do Produto', injeta o BOM UTF-8 (`\xEF\xBB\xBF`) e itera sobre a coleção em lotes de 200 registros.
- Criação de `Model/Export/ConvertToXml.php` tirando proveito de `Magento\Framework\Convert\ExcelFactory` para gerar uma stream em formato SpreadsheetML com as mesmas transformações de dados aplicadas.

**Implementação dos Controladores Dedicados (Controller/Adminhtml/Export):**

- Criação de `GridToCsv.php` e `GridToXml.php` sob o namespace `Webjump\Jhonatan\Controller\Adminhtml\Export`, implementando `HttpGetActionInterface` e `HttpPostActionInterface`.
- Definição explícita de `public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao_export'`, garantindo a interceptação imediata pelo módulo de autorização do Magento.

**Parametrização do Botão de Exportação (`webjump_avaliacao_listing.xml`):**

- Configuração do componente `<exportButton>` dentro de `listingToolbar` apontando as respectivas actions:
  - CSV: `webjump_avaliacao/export/gridToCsv`.
  - Excel XML: `webjump_avaliacao/export/gridToXml`.

**Reestruturação Topológica da ACL (`etc/acl.xml`):**

- Separação dos identificadores `Webjump_Jhonatan::avaliacao` e `Webjump_Jhonatan::avaliacao_export` como elementos irmãos sob `Webjump_Jhonatan::webjump`, extinguindo a herança indesejada de autorização implícita do motor Zend_Acl.

## 5. Resolução de Problemas Técnicos (Troubleshooting)

**Incompatibilidade na Chamada de Paginação (getTotalNumRecords):**

- **Causa:** Invocação direta do método `getTotalNumRecords()` na instância de `Magento\Ui\Component\MassAction\Filter`, inexistente na API pública da framework.
- **Resolução:** Extração do volume total via resultado da busca (`$searchResult->getTotalCount()`), estruturando a paginação em blocos através de `$searchResult->setPageSize(200)` e reciclagem de cursor via `$searchResult->clear()`.

**Exceção de Escrita de Stream no Conversor Excel (getData):**

- **Causa:** Tentativa de invocar `getData()` em `Magento\Framework\Convert\Excel`, que opera estritamente através da escrita direta em stream de arquivo.
- **Resolução:** Reconfiguração da factory com iterador em memória e callback de linha (`['iterator' => new ArrayIterator($dataRows), 'rowCallback' => [$this, 'getRowData']]`), delegando a persistência à stream do arquivo temporário com `$excel->write($stream, 'avaliacoes')`.

**Fuga de Autorização por Herança de Nós Filhos no Zend_Acl:**

- **Causa:** Recurso de exportação posicionado como nó descendente de avaliações, permitindo que operadores com acesso ao grid herdassem autorização de download mesmo com a caixa de exportação desmarcada.
- **Resolução:** Promoção do nó `avaliacao_export` a irmão independente de `avaliacao` no arquivo `acl.xml`, forçando o bloqueio estrito (HTTP 403 / Redirecionamento) a qualquer operador sem a permissão assinalada.

## 6. Comparativo Arquitetural: Justificativa da Estratégia B

| Vetor de Análise | Estratégia A (Preference Global sobre o Core) | Estratégia B (Controladores Próprios - Adotada) |
| --- | --- | --- |
| Raio de Impacto (Blast Radius) | Global: Interfere diretamente em todos os grids administrativos do sistema. | Zero: Circunscrito com exclusividade ao módulo de avaliações. |
| Risco de Regressão Transacional | Elevado: Falhas pontuais no conversor quebram a exportação de pedidos e clientes. | Zero: O core do Magento mantém a execução inalterada. |
| Governança de Segurança | Limitada: Rota compartilhada `mui/export/*` sem segregação de permissão granular. | Granular: Controlador isolado sob o recurso `avaliacao_export`. |
| Desempenho | Validações condicionais executadas continuamente em exportações de toda a loja. | Execução isolada acionada unicamente a pedido do usuário. |

## 7. Diagrama de Autorização e Governança ACL

```plaintext
                                [ Magento_Backend::admin ]
                                             │
                                             ▼
                             [ Webjump_Jhonatan::webjump ]
                                             │
                       ┌─────────────────────┴─────────────────────┐
                       ▼                                           ▼
         [ Webjump_Jhonatan::avaliacao ]       [ Webjump_Jhonatan::avaliacao_export ]
         (Visualizar e Operar o Grid)          (Exportar Arquivos CSV / XML)
```

A independência dos nós na árvore de autorização garante que a equipe de atendimento possa moderar avaliações no dia a dia sem possuir privilégios de extração em massa da base de dados.

## 8. Relatório de Quality Assurance (15 Evidências Homologadas)

**Evidência 01 — Menu de Opções do Botão Export**

Validação: Barra de ferramentas do grid de avaliações com o botão Export expandido, apresentando as opções CSV e Excel XML totalmente operacionais.

![Evidência 01](https://github.com/user-attachments/assets/0d952c1a-b27c-4b7e-b603-8e1306e92531)

**Evidência 02 — Planilha CSV com Formato Brasileiro e Nome do Produto**

Validação: Arquivo `avaliacoes.csv` aberto no LibreOffice Calc demonstrando a resolução da coluna "Nome do Produto" (Mouse Gamer Pro Wireless), a conversão do campo de status em "Sim" e "Não", o formato de data brasileiro (18/09/2026 06:29:58) e acentuação UTF-8 preservada.

![Evidência 02](https://github.com/user-attachments/assets/1e3309e2-7a85-4cd7-ba31-0ea636afe13d)

**Evidência 03 — Planilha Excel XML com Formato Brasileiro**

Validação: Arquivo `avaliacoes.xml` aberto no LibreOffice Calc confirmando a estrutura SpreadsheetML com as 8 colunas formatadas e ausência de tags corrompidas.

![Evidência 03](https://github.com/user-attachments/assets/ba075a88-c02e-410b-94a2-b6b1dc0941be)

**Evidência 04 — Exportação CSV Respeitando Filtro Textual (Autor)**

Validação: Tela comprovando a exportação estrita do registro correspondente ao autor Lucas Ferreira mediante aplicação de filtro de texto no grid.

![Evidência 04](https://github.com/user-attachments/assets/651726ed-58c4-4051-9f43-3b1d1aca2ae0)

**Evidência 05 — Exportação CSV Respeitando Filtro de Intervalo (Nota)**

Validação: Grid com filtro ativo Nota: 5 - 5 (2 records found) e planilha CSV aberta contendo unicamente as duas avaliações de nota máxima.

![Evidência 05](https://github.com/user-attachments/assets/30a9fc0e-a813-4b32-a508-e859730fe76c)

**Evidência 06 — Exportação Excel XML Respeitando Filtros**

Validação: Arquivo `avaliacoes.xml` aberto no LibreOffice Calc gerado sob filtro ativo, demonstrando paridade total de filtragem com o conversor CSV.

![Evidência 06](https://github.com/user-attachments/assets/b3a84ed7-a7fa-4731-ba6d-34a48d279153)

**Evidência 07 — Grid de Pedidos Nativo Operacional (Sales > Orders)**

Validação: Navegação em Sales > Orders com o grid padrão do core e o menu suspenso nativo de exportação intactos.

![Evidência 07](https://github.com/user-attachments/assets/ce96fcfa-2377-4cb8-8c3b-92937ad8d639)

**Evidência 08 — Conteúdo da Exportação Nativa de Pedidos**

Validação: Planilha CSV de pedidos aberta no LibreOffice Calc com cabeçalhos padrão do core (Purchase Point, Bill-to Name), atestando ausência de regressões.

![Evidência 08](https://github.com/user-attachments/assets/5c7325f0-c038-47a7-9565-8471469d582f)

**Evidência 09 — Grid de Clientes Nativo Operacional (Customers > All Customers)**

Validação: Tela de listagem de clientes carregada com a barra de ferramentas e funcionalidade de exportação nativas plenamente funcionais.

![Evidência 09](https://github.com/user-attachments/assets/630ad11a-c5ac-4433-a529-7b538c9cdc38)

**Evidência 10 — Configuração de Perfil Restrito sem Permissão de Exportação**

Validação: Árvore de permissões em System > Permissions > User Roles comprovando a arquitetura de recursos irmãos: "Avaliações" marcado e "Exportar Avaliações" desmarcado.

![Evidência 10](https://github.com/user-attachments/assets/055d27a1-9004-49ab-bcff-ef1ccb0cb7e5)

**Evidência 11 — Bloqueio de Acesso por URL Direta ao Endpoint de Exportação**

Validação: Sessão em janela anônima com usuário restrito interceptada ao acessar diretamente a rota administrativa, apresentando a mensagem de bloqueio formal do Magento.

![Evidência 11](https://github.com/user-attachments/assets/4eb1beeb-8d5e-4044-8898-c6637f333599)

**Evidência 12 — Código dos Controladores com ADMIN_RESOURCE Segregado (CLI)**

Validação: Inspeção no terminal dos controladores `GridToCsv.php` e `GridToXml.php`, destacando a declaração explícita de `ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao_export'`.

![Evidência 12](https://github.com/user-attachments/assets/2a0f7267-5f5d-4059-a96d-3b0ba4353a65)

**Evidência 13 — Código do Conversor com BOM UTF-8 e Resolução Dinâmica (CLI)**

Validação: Visualização do método `getCsvFile()` no terminal comprovando a injeção do BOM UTF-8, o vetor de cabeçalhos e a paginação em streaming.

![Evidência 13](https://github.com/user-attachments/assets/93355cd1-9285-4c00-9c1d-fadd5c322c82)

**Evidência 14 — Sucesso da Compilação de DI e Código Gerado (CLI)**

Validação: Execução do comando `bin/magento setup:di:compile` finalizada com 100% de sucesso sem erros de tipagem estrita ou injeção de dependências.

![Evidência 14](https://github.com/user-attachments/assets/0a4345ab-5700-44dd-8509-f1a8a256132e)

**Evidência 15 — Inviolabilidade do Core e Status do Repositório (CLI)**

Validação: Retorno de `git status -uall` atestando que todas as alterações estão circunscritas a `src/app/code/Webjump/Jhonatan/`, mantendo a pasta `vendor/` intocada.

![Evidência 15](https://github.com/user-attachments/assets/f9ace237-fb9f-42f4-9461-dfddef41f608)