-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12/08/2026 às 23:40
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `patrimonio`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `bens_moveis`
--

CREATE TABLE `bens_moveis` (
  `id` int(11) NOT NULL,
  `numero_tombamento` int(10) UNSIGNED DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `numero_empenho` varchar(100) NOT NULL,
  `data_aquisicao` date NOT NULL,
  `numero_nota` varchar(100) NOT NULL,
  `setor` varchar(255) NOT NULL,
  `subsetor` varchar(255) NOT NULL,
  `unidade` varchar(255) DEFAULT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `estado` varchar(50) NOT NULL,
  `tipo_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `imagens` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`imagens`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `cnpj` varchar(255) NOT NULL DEFAULT 'prefeitura',
  `setor_original` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `grupos`
--

CREATE TABLE `grupos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_auditoria`
--

CREATE TABLE `log_auditoria` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` enum('cadastro','edicao','exclusao','movimento') NOT NULL,
  `tabela_afetada` varchar(50) NOT NULL,
  `registro_id` int(11) NOT NULL,
  `dados_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_antes`)),
  `dados_depois` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_depois`)),
  `ip` varchar(45) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_login_falho`
--

CREATE TABLE `log_login_falho` (
  `id` int(11) NOT NULL,
  `usuario_tentado` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `setores`
--

CREATE TABLE `setores` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `subsetores`
--

CREATE TABLE `subsetores` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `setor` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tentativas_login`
--

CREATE TABLE `tentativas_login` (
  `id` int(11) NOT NULL,
  `identificador` varchar(255) NOT NULL,
  `tipo` enum('ip','email') NOT NULL,
  `tentativas` int(11) NOT NULL DEFAULT 1,
  `primeira_tentativa` datetime NOT NULL,
  `ultima_tentativa` datetime NOT NULL,
  `bloqueado_ate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos`
--

CREATE TABLE `tipos` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `unidades`
--

CREATE TABLE `unidades` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `setor` varchar(255) NOT NULL,
  `subsetor` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `cnpj` varchar(255) NOT NULL DEFAULT 'prefeitura'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Usuário administrador padrão para o primeiro acesso
-- Login: admin  |  Senha: admin
-- IMPORTANTE: troque essa senha assim que acessar o sistema (menu Configurações)
--
INSERT INTO `usuarios` (`usuario`, `nome`, `email`, `senha`, `cnpj`) VALUES
('admin', 'Administrador', 'admin@sistombamento.com', '$2a$12$ZwhcrQp6xxudgekLEJUxc.Ksdpds.5LuHwuWyHuBu2lUdQHbTKBk6', 'prefeitura');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `bens_moveis`
--
ALTER TABLE `bens_moveis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bens_moveis_grupo` (`grupo_id`),
  ADD KEY `fk_bens_moveis_tipo` (`tipo_id`),
  ADD KEY `fk_bens_moveis_setor_original` (`setor_original`),
  ADD KEY `fk_bens_moveis_created_by` (`created_by`);

--
-- Índices de tabela `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `log_auditoria`
--
ALTER TABLE `log_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_registro` (`tabela_afetada`,`registro_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_data` (`criado_em`);

--
-- Índices de tabela `log_login_falho`
--
ALTER TABLE `log_login_falho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip`),
  ADD KEY `idx_usuario` (`usuario_tentado`),
  ADD KEY `idx_data` (`criado_em`);

--
-- Índices de tabela `setores`
--
ALTER TABLE `setores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `subsetores`
--
ALTER TABLE `subsetores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_identificador_tipo` (`identificador`,`tipo`),
  ADD KEY `idx_ultima_tentativa` (`ultima_tentativa`);

--
-- Índices de tabela `tipos`
--
ALTER TABLE `tipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tipos_tipo` (`tipo`);

--
-- Índices de tabela `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario` (`usuario`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `bens_moveis`
--
ALTER TABLE `bens_moveis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `log_auditoria`
--
ALTER TABLE `log_auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `log_login_falho`
--
ALTER TABLE `log_login_falho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `setores`
--
ALTER TABLE `setores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `subsetores`
--
ALTER TABLE `subsetores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos`
--
ALTER TABLE `tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `bens_moveis`
--
ALTER TABLE `bens_moveis`
  ADD CONSTRAINT `fk_bens_moveis_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bens_moveis_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bens_moveis_setor_original` FOREIGN KEY (`setor_original`) REFERENCES `setores` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bens_moveis_tipo` FOREIGN KEY (`tipo_id`) REFERENCES `tipos` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `log_auditoria`
--
ALTER TABLE `log_auditoria`
  ADD CONSTRAINT `log_auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;