# 📦 SIS-Tombamento

**Sistema de Gestão Patrimonial (Tombamento de Bens Móveis)** desenvolvido para atender a rotina de controle de patrimônio de uma prefeitura — do cadastro do bem à emissão de relatórios oficiais em PDF, com trilha de auditoria completa.

Projeto construído do zero (backend, frontend e modelagem de banco) para substituir um processo manual baseado em livro de tombo físico, hoje em uso real pelo setor de tombamento da prefeitura.

> Desenvolvido de forma independente por [Rodrigo Delgado](#) — profissional de TI autodidata migrando para desenvolvimento de software.

---

## 🖼️ Preview

> <img width="929" height="621" alt="image" src="https://github.com/user-attachments/assets/973c2038-0434-48a5-8db8-82e3f020d425" />

> <img width="1286" height="629" alt="image" src="https://github.com/user-attachments/assets/f4520384-e5af-48af-a3c0-b0c611f51e45" />

> <img width="1292" height="622" alt="image" src="https://github.com/user-attachments/assets/c9b30c48-5008-4e4a-905f-404427b2dbd5" />

---

## 🎯 Motivação

Órgãos públicos frequentemente controlam patrimônio (móveis, equipamentos, veículos) por meio de planilhas ou livros físicos, sem rastreabilidade de quem alterou o quê, sem histórico de movimentação entre setores e sem relatórios padronizados para auditoria. O SIS-Tombamento nasceu para resolver esse problema em um contexto real de uso, com foco em **integridade dos dados**, **rastreabilidade** e **usabilidade para servidores não-técnicos**.

---

## ✨ Funcionalidades

- **Cadastro de bens móveis** com numeração de tombamento sequencial automática, cadastro individual ou em massa (com reaproveitamento de dados entre itens) e upload de imagens.
- **Multi-tenant por órgão (CNPJ)** — o mesmo sistema atende múltiplos CNPJs (ex: Prefeitura, Secretaria de Saúde) mantendo os dados de cada uma isolados logicamente.
- **Consulta pública por tombamento** — página pública (pensada para acesso via etiqueta/QR code no bem) exibindo os dados do item, com navegação entre bens anterior/próximo.
- **Movimentação de bens entre setores/subsetores/unidades**, com distinção entre "setor de origem" (da nota de aquisição) e "setor atual", incluindo janela de correção de 3 dias após o cadastro.
- **Trilha de auditoria (audit log)** de todas as ações sensíveis — quem cadastrou, editou, excluiu ou movimentou um bem, com snapshot dos dados antes/depois em JSON.
- **Relatórios em PDF** (via Dompdf, A4 paisagem) com marca d'água, filtros por período/grupo/unidade/setor/subsetor, e relatório dedicado de movimentações com destaque visual (verde) para os campos alterados.
- **Autenticação segura**: sessão + hash de senha com `password_hash`/`bcrypt`, tokens CSRF em todos os formulários, e **rate limiting progressivo** de tentativas de login (por IP e por e-mail, com bloqueio escalonado de 30s a 15min) e log de tentativas de login falhas.
- **Taxonomia normalizada**: grupos e tipos foram migrados de texto fixo no código para tabelas próprias (`grupos`, `tipos`) referenciadas por chave estrangeira, eliminando duplicação e preparando o terreno para gestão via painel (hoje o cadastro dessas tabelas ainda é feito diretamente no banco). Setores, subsetores e unidades já contam com página de cadastro que podem ser utilizadas pelo próprio usuário.
- **Área de configurações** para troca de senha do usuário autenticado.

---

## 🏗️ Arquitetura (simplificada)

```
sis-tombamento/
├── index.php              # Landing page
├── database/
│   └── db.sql              # Schema completo + usuário admin inicial
├── bens/                  # Área pública (consulta por tombamento / QR code)
│   ├── prefeitura.php
│   └── saude.php
└── painel/                 # Área administrativa (autenticada)
    ├── autenticar.php      # Login + rate limiting
    ├── rate_limit.php      # Bloqueio progressivo de tentativas
    ├── log.php             # Auditoria (log_auditoria, log_login_falho)
    ├── conexao.php         # Conexão PDO
    ├── cadastro-bem-movel.php / cadastrar-bem-movel.php
    ├── alteracao-bem-movel.php / alterar-bem-movel.php
    ├── excluir-bem-movel.php
    ├── relatorio-bens-moveis.php / gerar-relatorio-bens-moveis.php
    ├── relatorio-movimentos.php / gerar-relatorio-movimentos.php
    ├── cadastro-setor.php, cadastro-subsetor.php, cadastro-unidade.php
    ├── configuracoes.php / processar-configuracoes.php
    ├── includes/            # header/footer compartilhados
    ├── css/                 # Estilos por página (tema dark + acentos laranja)
    └── vendor/              # Dependências via Composer
```

**Padrão adotado:** cada funcionalidade é dividida em uma página de exibição (`*-bem-movel.php`) e um script de processamento (`cadastrar-*.php`, `alterar-*.php`) que recebe o POST, valida, executa a transação no banco e registra a auditoria — mantendo a lógica de negócio fora do HTML sempre que possível.

**Modelagem:** as entidades `grupos` e `tipos` foram normalizadas em tabelas próprias e referenciadas por chave estrangeira (`grupo_id`, `tipo_id`), substituindo valores fixos em código — decisão tomada já durante a evolução do projeto, ao perceber a limitação de manter essas listas hardcoded. `setor`, `subsetor` e `unidade` ainda são colunas de texto livre e estão no roadmap para o mesmo tratamento.

---

## 🛠️ Stack técnica

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8+ (procedural, orientado a funções, sem framework) |
| Banco de dados | MySQL / MariaDB via PDO (prepared statements) |
| Geração de PDF | [Dompdf](https://github.com/dompdf/dompdf) |
| Autenticação | Sessões PHP, `password_hash` (bcrypt), tokens CSRF |
| Segurança | Rate limiting progressivo, log de auditoria, `.htaccess` para controle de acesso, variáveis de ambiente via [`vlucas/phpdotenv`](https://github.com/vlucas/phpdotenv) |
| Frontend | HTML5, CSS3 (tema dark customizado, sem framework CSS), JS vanilla |
| Dependências | Composer |
| Ambiente de dev | XAMPP (Apache + MySQL) |

---

## 🔐 Segurança

Pontos implementados com atenção especial, pensando em um sistema real de uso público:

- Todas as queries usam **prepared statements** via PDO — sem concatenação de SQL.
- Senhas nunca armazenadas em texto puro (`password_hash`/`password_verify`).
- **Proteção CSRF** em todos os formulários de escrita.
- **Rate limiting** de login por IP e por e-mail, com bloqueio progressivo (3 tentativas livres → bloqueios de 30s até 15min).
- **Log de auditoria** imutável por aplicação (dados antes/depois em JSON) para qualquer ação sensível — essencial em um sistema de patrimônio público, onde rastreabilidade é requisito.
- Saída sempre escapada com `htmlspecialchars` para prevenir XSS.
- **Credenciais de banco via variáveis de ambiente** (`.env`, com `vlucas/phpdotenv`).

> ⚠️ **Importante:** o `database/db.sql` cria um usuário administrativo padrão (`admin@sistombamento.com` / `admin1234`) só para facilitar o setup local de quem for clonar o projeto. **Troque essa senha imediatamente após a primeira execução** (pela própria tela de Configurações do sistema) — nunca use essa credencial em um ambiente exposto publicamente.

---

## 🚀 Rodando localmente

Este projeto foi desenvolvido e testado com **XAMPP** (Apache + PHP + MySQL/MariaDB), então o tutorial abaixo assume esse ambiente.

### Pré-requisitos

- [XAMPP](https://www.apachefriends.org/) instalado, com **Apache** e **MySQL** — os dois módulos ligados pelo painel de controle do XAMPP.
- [Composer](https://getcomposer.org/) instalado e disponível no PATH do sistema.
- Git.

### Passo a passo

**1. Clone o repositório dentro da pasta `htdocs` do XAMPP**

```bash
cd C:\xampp\htdocs
git clone https://github.com/delgadoz/sis-tombamento.git
cd sis-tombamento
```

> No Linux, o caminho equivalente costuma ser `/opt/lampp/htdocs` (XAMPP) ou `/var/www/html` (LAMP nativo).

**2. Instale as dependências PHP**

```bash
cd painel
composer install
cd ..
```

**3. Crie o banco de dados e importe o schema**

Abra o **phpMyAdmin** (`http://localhost/phpmyadmin`, com o Apache e o MySQL do XAMPP ligados), crie um banco chamado `patrimonio` e importe o arquivo `database/db.sql` pela aba "Importar".

Se preferir via terminal, com o MySQL do XAMPP no PATH:

```bash
mysql -u root -p -e "CREATE DATABASE patrimonio"
mysql -u root -p patrimonio < database/db.sql
```

> No XAMPP padrão, o usuário `root` do MySQL não tem senha — pressione Enter em branco quando for solicitado.

**4. Configure as variáveis de ambiente**

```bash
# Windows (CMD)
copy .env.example .env

# Linux / macOS
cp .env.example .env
```

Edite o `.env` criado com os dados do seu MySQL local:

```
DB_HOST=localhost
DB_NAME=patrimonio
DB_USER=root
DB_PASS=
```

**5. Ligue o Apache pelo painel do XAMPP**

Com Apache e MySQL ligados no painel de controle do XAMPP, acesse:

- `http://localhost/sis-tombamento` — landing page pública
- `http://localhost/sis-tombamento/painel/login` — área administrativa

O `database/db.sql` já cria um usuário inicial (`admin@sistombamento.com` / `admin1234`) para o primeiro acesso. **Troque essa senha assim que entrar** (menu Configurações).

---

## 🗺️ Roadmap

- [ ] Testes automatizados (parte da suíte com Selenium/Python já foi explorada como exercício)
- [ ] Módulos de cadastro, alteração e relatórios de bens imóveis (já prevista na estrutura atual do menu)
- [ ] Módulos com controle de acesso para cadastro, alteração e exclusão de usuários
- [ ] Módulo para exclusão de Setor, Subsetor e Unidade
- [ ] Migrar as colunas setor, subsetor e unidade de colunas de texto para chaves estrangeiras (mesmo padrão já aplicado a grupo_id/tipo_id), refatorando os pontos do código que hoje dependem desses campos como texto livre
- [ ] Adicionar seleção de CNPJ (organização/secretaria) nas Configurações, permitindo ao usuário alternar o contexto de operação sem precisar alterar diretamente no banco de dados

---

## 👤 Autor

Projeto desenvolvido e mantido por **Rodrigo Delgado**, autodidata em programação (PHP, Python, MySQL) há cerca de 13 anos, atualmente em transição de TI Suporte para desenvolvimento de software.

- [LinkedIn](https://www.linkedin.com/in/rodrigo-delgado-50b65b236/)
- [GitHub](https://github.com/delgadoz)

---

## 📄 Licença

MIT
