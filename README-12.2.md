# Operação de Catálogo, CMS e Estrutura Multi-Store no Magento 2

## 1. Visão Geral do Entregável

Este repositório formaliza a entrega técnica do **Desafio 12.2 (Explorando a loja e o catálogo)**. O foco desta etapa foi colocar a mão na massa no painel do Magento Open Source, saindo da camada de infraestrutura conteinerizada para operar diretamente as regras de negócio da plataforma. 

A implementação cobriu quatro frentes operacionais:
1. **Modelagem de Catálogo:** Criação de categorias e produtos físicos com integridade de dados e atributos exigidos pelo sistema.
2. **Sincronização via CLI:** Uso do terminal para reindexar as tabelas do banco de dados e atualizar o motor de busca do OpenSearch.
3. **Composição Modular (CMS):** Isolamento de peças visuais em blocos estáticos e injeção dinâmica em páginas através do Page Builder nativo.
4. **Customização de Escopo (Storefront):** Alteração de parâmetros globais de exibição, sobrepondo o comportamento padrão de layout da vitrine.

---

## 2. Rastreabilidade dos Critérios de Aceite

| Requisito do Desafio | O que foi feito | Status | Evidência |
| :--- | :--- | :--- | :--- |
| **Criação de Categoria** | Subcategoria `Periféricos` criada na `Default Category` e adicionada ao menu. | **Concluído** | Evidência 01 |
| **Criação de Produto** | Cadastro do `Mouse Gamer Pro Wireless` com SKU, Preço, Estoque, Peso e Foto. | **Concluído** | Evidência 02 |
| **Catálogo na Vitrine** | Validação do card do produto renderizado na vitrine após a reindexação. | **Concluído** | Evidência 03 |
| **Bloco CMS Modular** | Bloco promocional estilizado construído via componente HTML no Page Builder. | **Concluído** | Evidência 04 |
| **Página CMS Dinâmica** | Landing Page `/ofertas` instanciando o bloco de conteúdo dinamicamente. | **Concluído** | Evidência 05 |
| **Configuração de Sistema** | Alteração no layout da categoria em `Stores > Configuration` para `List Only`. | **Concluído** | Evidência 06 |
| **Efeito Visual no Frontend** | Vitrine `/perifericos.html` reestruturada visualmente para lista horizontal. | **Concluído** | Evidência 07 |
| **Operação via Terminal** | Execução síncrona de `indexer:reindex` e limpeza agressiva no Redis. | **Concluído** | Evidência 08 |

---

## 3. Entendendo Website, Store e Store View (Por Trás do Multi-Loja)

O que diferencia o Magento no mercado corporativo é sua capacidade de rodar várias operações de e-commerce complexas usando uma única base de código e um só painel administrativo. Em vez de subir servidores clonados para cada nova marca da empresa, a arquitetura divide a governança em 3 camadas:

1. **Website (A Camada de Negócios):** É o nível mais alto. É aqui que definimos as regras pesadas: quais formas de pagamento funcionam, quais transportadoras entregam, a moeda de conversão e se o preço de um produto vale para todas as lojas ou muda de um mercado para outro. É também no Website que isolamos a segurança dos usuários (decidindo se o cliente usa o mesmo login na Loja A e na Loja B ou se as contas são restritas).
2. **Store (A Camada de Catálogo):** Fica no meio. A principal responsabilidade técnica de uma Store é apontar para a Árvore de Categorias Raiz (*Root Category*). Se uma empresa quer vender celulares num site e sapatos no outro, basta criar duas Stores apontando para menus diferentes. O banco e o estoque físico são compartilhados por trás, mas a navegação é totalmente isolada.
3. **Store View (A Camada de Vitrine):** É o que o consumidor realmente acessa. Serve estritamente para lidar com apresentação: idioma local, formatação da moeda e temas visuais. O produto no galpão de estoque é rigorosamente o mesmo, mas o seu título e descrição podem ser lidos em Português na "Store View BR" e em Espanhol na "Store View ES", sem necessidade de duplicar dados no banco.

### Comparativo Rápido com o Mercado:
* **Shopify:** Operações multi-lojas robustas ou independentes costumam exigir a contratação de instâncias separadas (*Shopify Plus*) ou dependem do recurso *Markets* (que tem limitações de personalização sob o guarda-chuva da nuvem).
* **AEM (Adobe Experience Manager):** O problema de multi-site é resolvido criando diferentes nós e ramificações no repositório JCR (como `/content/site/pt_br` e `/content/site/en_us`), orquestrados através do *Multi-Site Manager (MSM)* e de *Live Copies*.
* **Magento:** Tudo é resolvido de forma nativa nas colunas do banco de dados relacional. O lojista troca o comportamento, os idiomas ou as integrações simplesmente selecionando a visão (escopo) num dropdown do painel administrativo.

---

## 4. O Modelo EAV e por que a Reindexação é Obrigatória

No Magento, um produto não é salvo numa linha de tabela SQL tradicional (com uma coluna fixa para nome, outra para preço e outra para descrição). A plataforma trabalha sob a arquitetura **EAV (Entity-Attribute-Value)**. 

Isso significa que os dados de um único produto ficam "espalhados" em dezenas de tabelas de acordo com o tipo da informação (`_varchar` guarda textos curtos, `_decimal` guarda preços, `_int` guarda status). A vantagem absurda disso é que a loja pode criar atributos novos ("Voltagem", "Tamanho do Aro", "Cor") na hora pelo painel, sem acionar um desenvolvedor para rodar scripts de migração estrutural no banco de dados.

**O gargalo e a solução (Indexers):**
A flexibilidade do EAV custa muito caro no processamento. Para carregar uma vitrine simples, o banco teria que fazer milhares de operações `JOIN` para juntar as tabelas fragmentadas, derrubando a performance.
Por isso rodamos o comando `bin/magento indexer:reindex` no terminal. O Magento pega esses dados espalhados, "achata" tudo em tabelas planas de leitura super-rápida (*flat tables*) e envia para o OpenSearch. É por isso que um produto salvo no painel continua invisível na loja até o indexador agir.

---

## 5. Passo a Passo do Desenvolvimento

1. **Catálogo:** Em `Catalog > Categories`, a taxonomia da loja foi expandida. Em `Catalog > Products`, o item físico foi criado garantindo o preenchimento dos metadados rigorosos de logística (peso nominal) e inventário (quantidade inicial de 50).
2. **CMS Dinâmico:** Em `Content > Blocks`, o banner promocional foi isolado num bloco estático. Em `Content > Pages`, a página `/ofertas` foi criada injetando esse bloco através da ferramenta nativa de Widget, promovendo manutenibilidade centralizada.
3. **Parametrização:** Em `Stores > Configuration > Catalog > Storefront`, a herança de layout do sistema foi quebrada. A renderização foi forçada para o layout de linha (`List Only`), provando a capacidade de controle de vitrine pelo administrador.
4. **Terminal:** O ciclo foi fechado no WSL2 com a compilação de índices (`indexer:reindex`) e limpeza absoluta dos nós de cache de configuração e blocos (`cache:clean`), enviando as mudanças imediatamente para o front.

---

## 6. Relatório Detalhado de Quality Assurance (QA)

Abaixo estão apresentadas as 8 evidências formais de homologação da entrega, comprovando cada etapa operacional (imagens hospedadas externamente via CDN para otimização do repositório Git).

### Evidência 01 — Categoria Instanciada no Catálogo
Comprova a expansão da taxonomia da loja. A subcategoria `Periféricos` encontra-se ancorada corretamente na árvore de navegação com as opções ativas no painel.

<img width="1916" height="1027" alt="01-categoria-criada-admin" src="https://github.com/user-attachments/assets/20ec1b4b-873d-4b3c-bec0-a1f46f694a6f" />

### Evidência 02 — Estrutura de Produto Simples no Admin
Demonstra o preenchimento satisfatório e persistido no banco da entidade `Mouse Gamer Pro Wireless`, com SKU, preço, alocação física de estoque e mídia.

<img width="1900" height="1022" alt="02-produto-criado-admin" src="https://github.com/user-attachments/assets/a34b2bc9-ea60-41b8-97aa-eb446bfe0307" />

### Evidência 03 — Resolução de Catálogo no Frontend
Valida a integração backend/vitrine após a reindexação. O produto responde na loja local (`/perifericos.html`) no layout inicial em formato de grade.

<img width="1917" height="1027" alt="03-categoria-produto-frontend" src="https://github.com/user-attachments/assets/ad7dda96-986f-452b-aede-dab3b754cf4b" />

### Evidência 04 — Construção de UI via Bloco CMS
Painel confirmando o salvamento independente do bloco estático de promoção, estilizado usando HTML no Page Builder para fácil injeção.

<img width="1897" height="1027" alt="04-bloco-cms-admin" src="https://github.com/user-attachments/assets/a6436cf9-a475-4068-9bbf-b4c8f8304d61" />

### Evidência 05 — Injeção de Widget em Página CMS
A Landing Page (`/ofertas`) exibe o funcionamento em produção do Widget, instanciando o bloco de forma fluida junto ao título da página.

<img width="1916" height="1027" alt="05-pagina-cms-frontend" src="https://github.com/user-attachments/assets/47226f62-709a-43da-8313-99580ed3236d" />

### Evidência 06 — Alteração de Escopo de Configuração (Storefront)
Demonstra a quebra da herança de configurações padrão. O parâmetro `List Mode` modificado no painel para forçar a restrição visual em todas as listagens da categoria.

<img width="1897" height="1027" alt="06-configuracao-alterada-admin" src="https://github.com/user-attachments/assets/fbd6f5ab-8ae3-4b2d-9d72-3a7e5060ed84" />

### Evidência 07 — Mutação Estrutural de Renderização no Frontend
Homologação imediata no frontend. A rota `/perifericos.html` perde o grid e estrutura seus itens em linhas horizontais contendo descrições laterais e CTAs de compra.

<img width="1916" height="1027" alt="07-efeito-configuracao-frontend" src="https://github.com/user-attachments/assets/99de4db9-b677-4398-ab33-e632b9d852f0" />

### Evidência 08 — Orquestração de Indexadores via CLI
Comprova a telemetria do ambiente local no terminal Ubuntu, validando a reconstrução dos 14 índices síncronos e a purga segura dos caches da aplicação.

<img width="760" height="636" alt="08-cli-reindex-cache" src="https://github.com/user-attachments/assets/631fbea0-c12f-407f-93f6-9996209078a9" />