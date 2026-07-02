# CASPTI Mini ERP

O **CASPTI Mini ERP** é uma aplicação web nativa em PHP projetada para ambientes de hospedagem legados com compatibilidade para o MySQL 4.1+. O sistema possui uma arquitetura inspirada no ecossistema do Microsoft Dynamics AX, utilizando um modelo de dados estruturado para gestão de entidades, lançamentos fiscais, diários contábeis e controle de acessos modular.

---

## 🚀 Principais Funcionalidades

- **Autenticação & Segurança**: Fluxo de autenticação baseado em sessões e tokens OAuth2 com expiração controlada e suporte a níveis de permissão (Roles).
- **Catálogo Global de Endereços (Global Address Book)**: Estrutura integrada inspirada no Dynamics AX com suporte a `DirPartyTable`, `LogisticsPostalAddress` e `LogisticsElectronicAddress`.
- **Gestão de Clientes e Fornecedores**: Identificadores únicos padrão AX para facilitar o rastreamento e integração.
- **Produtos & Serviços**: Cadastro unificado de itens, serviços prestados e códigos de serviços fiscais.
- **Faturamento & Compras**: Emissão e controle de faturas de serviço (Invoices) e pedidos de compra (Purchase Orders).
- **Diário Contábil & Lançamentos**: Módulo de diário contábil (`JournalTable`/`JournalTrans`) com fluxos de conciliação financeira.
- **Relatórios Financeiros**: Visualização dinâmica de faturamentos, despesas e fluxos de caixa com totalizadores no rodapé.
- **Internacionalização (i18n) & Menu Dinâmico**: 
  - Idioma do usuário armazenado no perfil (`SysUserInfo.LanguageId`).
  - Suporte inicial a `PT-BR` e `EN-US`.
  - Rótulos da interface (`SysLabelText`) e itens do menu (`SysMenuGroup`, `SysMenuItem`) carregados dinamicamente com base nas permissões e idioma do usuário.

---

## 🛠️ Stack Tecnológica

- **Backend**: PHP Nativo (estruturado com Front Controller, Router customizado e controllers baseados em JSON API).
- **Banco de Dados**: MySQL 4.1+ (DDL completa e carga inicial com dados de semente no arquivo `schema.sql`).
- **Frontend**: SPA (Single Page Application) leve, sem dependências pesadas, construída com JavaScript puro (Vanilla JS), chamadas assíncronas (Fetch API) e estilizada com CSS customizado e responsivo.
- **Ambiente Local**: PHP portátil integrado para desenvolvimento rápido offline.

---

## 📁 Estrutura de Pastas do Projeto

- [index.php](file:///D:/SourceRepos/azevedosgustavo/erpmini/index.php): Front Controller do projeto. Inicializa a aplicação, define as rotas da API e renderiza a SPA.
- [schema.sql](file:///D:/SourceRepos/azevedosgustavo/erpmini/schema.sql): DDL completo do banco de dados e sementes iniciais.
- `app/`:
  - `config/`: Configurações de conexão ao banco e variáveis globais do sistema.
  - `core/`: Utilitários centrais como roteador (`Router`), sessão, controle de autenticação e ajudantes de banco de dados (`Db`).
  - `controllers/`: Controladores responsáveis por expor os endpoints JSON utilizados pelo frontend.
  - `models/`: Classes de acesso a dados (Data Access Objects) e regras de negócios (faturamento, relatórios, diários).
  - `views/`: Layout HTML principal da SPA.
- `public/`:
  - `css/app.css`: Estilização principal, temas visuais, menu dinâmico e responsividade.
  - `js/api.js`: Cliente HTTP centralizado utilizando Fetch API.
  - `js/app.js`: Lógica da SPA, renderização dinâmica de grades (grids), formulários e processamento de menus.
- `scripts/`:
  - Scripts de manutenção, migração de dados e utilitários auxiliares (carga de dados históricos, importação de extratos bancários com scripts Python, reorganização de menus, etc.).
- `tools/`:
  - Executável do PHP local para agilizar o desenvolvimento local.

---

## 💻 Configuração e Execução Local

### 1. Requisitos Prévios

- Servidor MySQL (ou MariaDB) instalado e em execução.
- PHP local (já incluso no repositório em `tools/php/php.exe` para Windows).

### 2. Configurando o Banco de Dados

1. Crie um banco de dados MySQL de sua preferência (ex: `erpminiprod`).
2. Configure as credenciais editando o arquivo `app/config/config.php` ou exportando as seguintes variáveis de ambiente:
   - `MINIERP_DB_HOST`
   - `MINIERP_DB_PORT`
   - `MINIERP_DB_NAME`
   - `MINIERP_DB_USER`
   - `MINIERP_DB_PASSWORD`
3. Execute o script `schema.sql` no seu banco de dados ou use o utilitário em PHP no terminal:
   ```bash
   tools/php/php.exe scripts/execute_schema.php
   ```

### 3. Executando o Servidor Local

No Windows, você pode iniciar o servidor local com o script PowerShell fornecido:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start_local_server.ps1
```

Isso iniciará o servidor embutido do PHP na porta `8080`.
- **URL de acesso**: [http://127.0.0.1:8080](http://127.0.0.1:8080)

---

## 🛠️ Scripts Úteis de Migração e Manutenção

O projeto conta com vários scripts de automação localizados em `scripts/` para facilitar tarefas administrativas:

- **Migração de Tradução e Navegação**:
  ```bash
  tools/php/php.exe scripts/apply_i18n_navigation_migration.php
  ```
- **Reset de Menus e Usuário Admin**:
  ```bash
  tools/php/php.exe scripts/apply_menu_and_user_reset.php
  ```
- **Importação e Conciliação de Extratos**:
  Scripts em Python (`scripts/import_*.py` e `scripts/reconcile_extrato.py`) que processam extratos bancários de 2025 e automatizam a correspondência com os diários contábeis de faturamento.
- **Configuração de Permissões de Menu por Perfil (Roles)**:
  ```bash
  tools/php/php.exe scripts/setup_role_menu_permissions.php
  ```