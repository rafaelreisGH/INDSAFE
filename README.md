# INDSAFE - Sistema de Apoio ao Planejamento Operacional e Gestão de Recursos

[![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=flat&logo=docker&logoColor=white)](https://www.docker.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-Proprietary%20%2F%20CBMMT-blue.svg)](#)

---

## 🇧🇷 Português (pt-BR)

### 📌 Sobre o Projeto
O **INDSAFE** é uma plataforma web desenvolvida para otimizar o planejamento operacional, resposta a incidentes e gerenciamento de recursos de combate a incêndio e emergências no Distrito Industrial de Cuiabá (MT). O sistema promove a integração entre as indústrias associadas (AEDIC) e o **Corpo de Bombeiros Militar de Mato Grosso (CBMMT)**.

Através do portal, as empresas mantêm seus dados técnicos atualizados (PSCIP, recursos hídricos, LGE - Líquido Gerador de Espuma, maquinários pesados e responsáveis técnicos de emergência), fornecendo subsídios em tempo real para tomada de decisão tática e socorro operacional célere.

---

### ✨ Principais Funcionalidades
- **Gestão de Empresas e Vínculos:** Cadastro e acompanhamento cadastral de indústrias, filiais e responsáveis técnicos.
- **Mapeamento de Recursos Operacionais:** Cadastro e busca especializada por:
  - Reservatórios e disponibilidade hídrica (vazão, volume e engates).
  - Líquido Gerador de Espuma (LGE) com tipos e dosagens.
  - Maquinários e equipamentos de apoio (pás carregadeiras, tratores, caminhões-pipa).
- **Controle de Acesso Baseado em Perfis (RBAC):** Painéis específicos para Usuário Geral, Representante da Empresa, Gestor Operacional e Administrador.
- **Trilha de Auditoria e Logs:** Integração com o Monolog persistindo ações críticas (CRUD e logins) no banco de dados via `DBHandler`.
- **APIs RESTful:** Endpoints em JSON consumidos via JavaScript nativo (`Fetch API`) para carregamento assíncrono e dinâmico.

---

### 🛠️ Tecnologias Utilizadas
- **Backend:** PHP 8.2 com Composer (PSR-4 autoloading).
- **Banco de Dados:** MySQL 8.0 gerenciado com conexões seguras via `PDO`.
- **Frontend:** HTML5, CSS3, JavaScript (Fetch API) e Bootstrap 5.
- **Bibliotecas:** `PHPMailer/PHPMailer` (envio de senhas/tokens) e `monolog/monolog` (auditoria).
- **Infraestrutura:** Docker e Docker Compose com ambientes desacoplados para desenvolvimento e produção.

---

### 🚀 Como Executar o Projeto

#### Pré-requisitos
- [Docker](https://www.docker.com/) & [Docker Compose](https://docs.docker.com/compose/)
- [Git](https://git-scm.com/)

#### Passo a Passo
1. **Clonar o Repositório:**
   ```bash
   git clone https://github.com/rafaelreisGH/INDSAFE.git
   cd INDSAFE
   ```

2. **Configurar as Variáveis de Ambiente:**
   Copie o arquivo de exemplo e configure suas credenciais locais:
   ```bash
   cp .env.example .env
   ```

3. **Subir os Containers (Desenvolvimento):**
   ```bash
   docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d --build
   ```

4. **Acessar os Serviços:**
   - **Aplicação Web:** [http://localhost:8080](http://localhost:8080)
   - **phpMyAdmin (Dev):** [http://localhost:8082](http://localhost:8082)

---

### 🔒 Práticas de Segurança e Conformidade
- **Proteção contra Vazamento de Segredos:** Todas as credenciais de banco e SMTP são consumidas via variáveis de ambiente (`.env`).
- **Prevenção a CSRF:** Formulários e fluxos de autenticação protegidos por tokens únicos por sessão.
- **Consultas Seguras:** Todas as consultas e manipulações no banco são realizadas via *Prepared Statements* (PDO), mitigando riscos de SQL Injection.
- **Logs de Auditoria:** Rastreabilidade completa de ações administrativas e operacionais.

---

<br>

## 🇺🇸 English (en-US)

### 📌 About the Project
**INDSAFE** is a web-based operational planning and emergency resource management platform built for the Industrial District of Cuiabá (Mato Grosso, Brazil). The system bridges local industries (AEDIC) and the **Military Fire Corps of Mato Grosso (CBMMT)** to enhance incident response and industrial fire prevention.

Through the portal, facilities maintain up-to-date technical safety records (fire safety compliance/PSCIP, water supplies, Aqueous Film-Forming Foam - AFFF/LGE, heavy machinery, and emergency focal points), providing real-time intelligence for tactical emergency response.

---

### ✨ Key Features
- **Facility & Technical Registry:** Registration and management of manufacturing plants, representatives, and safety managers.
- **Emergency Resource Mapping:** Dedicated query modules for:
  - Water reservoirs (capacity, flow rate, and connections).
  - Foam concentrates (AFFF/LGE quantities and specifications).
  - Heavy response machinery (loaders, tractors, water tankers).
- **Role-Based Access Control (RBAC):** Customized dashboards for Standard Users, Facility Representatives, Operational Managers, and System Admins.
- **Audit Trails & Security Logging:** Monolog integration routing state-changing operations (CRUD) directly to database tables via `DBHandler`.
- **RESTful Endpoints:** Modular JSON APIs consumed by client-side JavaScript (`Fetch API`) for asynchronous operations.

---

### 🛠️ Tech Stack
- **Backend:** PHP 8.2 with Composer dependency management.
- **Database:** MySQL 8.0 with `PDO` prepared statements.
- **Frontend:** HTML5, CSS3, modern Vanilla JavaScript (Fetch API), Bootstrap 5.
- **Core Packages:** `PHPMailer/PHPMailer` (notification workflows) and `monolog/monolog` (event logging).
- **DevOps:** Docker and Docker Compose (modular staging/production configurations).

---

### 🚀 Getting Started

#### Prerequisites
- [Docker](https://www.docker.com/) & [Docker Compose](https://docs.docker.com/compose/)
- [Git](https://git-scm.com/)

#### Installation
1. **Clone the repository:**
   ```bash
   git clone https://github.com/rafaelreisGH/INDSAFE.git
   cd INDSAFE
   ```

2. **Configure Environment Variables:**
   Duplicate the example configuration and adjust values as needed:
   ```bash
   cp .env.example .env
   ```

3. **Start Docker Containers (Development):**
   ```bash
   docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d --build
   ```

4. **Access the Services:**
   - **Web App:** [http://localhost:8080](http://localhost:8080)
   - **phpMyAdmin:** [http://localhost:8082](http://localhost:8082)

---

### 🛡️ Security & Engineering Standards
- **Zero Hardcoded Secrets:** Credentials for database and SMTP mailers are injected via environment variables (`.env`).
- **CSRF Protection:** State-changing forms utilize per-session cryptographic tokens.
- **SQL Injection Defense:** All queries are strictly parameterized via PDO prepared statements.
- **Audit Logging:** System operations maintain detailed contextual metadata for administrative oversight.

---

### 👤 Author
Desenvolvido por **Rafael Corrêa dos Reis**  
STIC / DAI - Corpo de Bombeiros Militar de Mato Grosso (CBMMT)
