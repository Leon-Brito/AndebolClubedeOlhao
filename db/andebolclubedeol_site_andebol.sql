-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 30-Jul-2026 às 23:27
-- Versão do servidor: 10.6.19-MariaDB
-- versão do PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `andebolclubedeol_site_andebol`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `convocados`
--

CREATE TABLE `convocados` (
  `id_convocatoria` int(11) DEFAULT NULL,
  `id_jogador` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `convocados`
--

INSERT INTO `convocados` (`id_convocatoria`, `id_jogador`) VALUES
(22, 17),
(23, 17),
(24, 17),
(25, 21),
(26, 21),
(27, 17),
(28, 17);

-- --------------------------------------------------------

--
-- Estrutura da tabela `convocatorias`
--

CREATE TABLE `convocatorias` (
  `id` int(11) NOT NULL,
  `jogo_contra` varchar(100) DEFAULT NULL,
  `data_jogo` datetime DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `local` varchar(255) DEFAULT NULL,
  `escalao` varchar(20) DEFAULT NULL,
  `id_criador` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `convocatorias`
--

INSERT INTO `convocatorias` (`id`, `jogo_contra`, `data_jogo`, `mensagem`, `local`, `escalao`, `id_criador`) VALUES
(1, 'porto', '2026-01-16 11:38:00', NULL, 'olhao', 'Sub-14', NULL),
(2, 'Gaia', '2026-01-31 16:49:00', NULL, 'Pavilhao municipal do porto', 'Sub-14', NULL),
(3, 'Gaia', '2026-01-31 16:49:00', NULL, 'Pavilhao municipal do porto', 'Sub-16', NULL),
(4, 'Leça', '2026-01-17 15:08:00', NULL, 'Pavilhao do leça', 'Sub-18', NULL),
(5, 'Leça', '2026-01-17 15:08:00', NULL, 'Pavilhao do leça', 'Sub-16', NULL),
(6, 'Leça', '2026-01-17 15:08:00', NULL, 'Pavilhao do leça', 'Sub-14', NULL),
(7, 'gaia', '2026-01-23 11:27:00', NULL, 'Gaia center', 'Sub-18', NULL),
(8, 'benfica', '2026-01-17 14:13:00', NULL, 'Luz', 'Sub-18', NULL),
(9, 'Benfica', '2026-01-17 15:34:00', NULL, 'Pavilhão da Luz', 'Sub-18', NULL),
(10, 'Benfica', '2026-01-16 15:35:00', NULL, 'Pavilhão da Luz', 'Sub-18', NULL),
(11, 'Benfica', '2026-01-24 22:46:00', NULL, 'Pavilhão da Luz', 'Sub-18', NULL),
(12, 'Benfica', '2026-01-24 22:46:00', NULL, 'Pavilhão da Luz', 'Sub-16', NULL),
(13, 'Benfica', '2026-01-31 13:52:00', NULL, 'Pavilhão da Luz Nº2', 'Sub-18', NULL),
(14, 'Benfica', '2026-02-04 12:56:00', NULL, 'Pavilhão da Luz', 'Sub-18', NULL),
(15, 'Benfica', '2026-02-20 20:57:00', NULL, 'Pavilhão da Luz', 'Sub-18', NULL),
(16, 'Benfica', '2026-02-21 20:59:00', NULL, 'Pavilhão da Luz', 'Sub-18', NULL),
(17, 'Benfica', '2026-05-29 16:45:00', NULL, 'Pavilhão da Luz', 'Sub-18', NULL),
(18, 'Sporting', '2026-05-30 16:26:00', NULL, 'Pavilhão João Rocha', 'Sub-18', NULL),
(19, 'Sporting', '2026-05-31 17:24:00', NULL, 'Pavilhão João Rocha', 'Sub-18', NULL),
(20, 'Sporting', '2026-05-28 19:12:00', NULL, 'Pavilhão João Rocha', 'Sub-16', NULL),
(21, 'Sporting', '2026-06-01 15:09:00', NULL, 'Pavilhão João Rocha', 'Sub-18', NULL),
(22, 'Casa Cultura de Loulé', '2026-05-23 17:00:00', NULL, 'Pavilhão 25 de Abril', 'Sub-18', 16),
(23, 'Clube Vela de Tavira', '2026-05-02 14:31:00', NULL, 'Pavilhão de Moncarapacho', 'Sub-18', 18),
(24, 'Benfica', '2026-06-27 17:30:00', NULL, 'Pavilhão da Luz', 'Sub-18', 18),
(25, 'Casa Cultura de Loulé', '2026-03-28 15:00:00', NULL, 'Pavilhão de Moncarapacho', 'Sub-14', 16),
(26, 'Clube Vela de Tavira', '2026-04-04 10:30:00', NULL, 'Pavilhão de Moncarapacho', 'Sub-14', 20),
(27, 'CCD Costa Doiro', '2026-06-06 15:30:00', NULL, 'Pavilhão Julio Dantas', 'Sub-18', 20),
(28, 'Queijas', '2026-08-01 20:23:00', NULL, 'Pavilhão de Moncarapacho', 'Sub-18', 16);

-- --------------------------------------------------------

--
-- Estrutura da tabela `encarregados_jogadores`
--

CREATE TABLE `encarregados_jogadores` (
  `id_encarregado` int(11) NOT NULL,
  `id_jogador` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `encarregados_jogadores`
--

INSERT INTO `encarregados_jogadores` (`id_encarregado`, `id_jogador`) VALUES
(19, 17),
(19, 21);

-- --------------------------------------------------------

--
-- Estrutura da tabela `faturas`
--

CREATE TABLE `faturas` (
  `id` int(11) NOT NULL,
  `numero_recibo` varchar(50) NOT NULL,
  `data_emissao` date NOT NULL,
  `id_jogador` int(11) DEFAULT NULL,
  `nome_jogador` varchar(100) DEFAULT NULL,
  `nif_jogador` varchar(20) DEFAULT NULL,
  `morada_jogador` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `valor_base` decimal(10,2) DEFAULT NULL,
  `valor_iva` decimal(10,2) DEFAULT NULL,
  `valor_desconto` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `metodo_pagamento` enum('Transferencia Bancaria','Dinheiro','MBWAY') DEFAULT NULL,
  `nota_pagamento` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `faturas`
--

INSERT INTO `faturas` (`id`, `numero_recibo`, `data_emissao`, `id_jogador`, `nome_jogador`, `nif_jogador`, `morada_jogador`, `descricao`, `valor_base`, `valor_iva`, `valor_desconto`, `valor_total`, `data_pagamento`, `metodo_pagamento`, `nota_pagamento`) VALUES
(22, '2026/06', '2026-06-01', 17, NULL, '268547852', NULL, 'Mensalidade de Junho', 15.00, 2.00, 0.00, 17.00, '2026-06-04', 'Transferencia Bancaria', 'pago'),
(23, '2026/01', '2026-07-02', 21, NULL, '963456789', NULL, 'Mensalidade', 15.00, 3.00, 0.00, 18.00, '2026-07-03', 'MBWAY', 'pago'),
(24, '2026/07', '2026-07-01', 17, NULL, '268547852', NULL, 'Mensalidade Julho', 15.00, 1.00, 0.00, 16.00, '2026-07-02', 'MBWAY', 'pago'),
(25, '2026/07', '2026-07-31', 17, 'Leonardo Brito', '268547852', 'R. Manuel António Pina 1', 'Mensalidade Agosto', 15.00, 1.00, 0.00, 16.00, '2026-08-01', 'Dinheiro', 'pago');

-- --------------------------------------------------------

--
-- Estrutura da tabela `jogador_escaloes`
--

CREATE TABLE `jogador_escaloes` (
  `id` int(11) NOT NULL,
  `id_jogador` int(11) DEFAULT NULL,
  `escalao` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `jogador_escaloes`
--

INSERT INTO `jogador_escaloes` (`id`, `id_jogador`, `escalao`) VALUES
(24, 17, 'Sub-18'),
(25, 21, 'Sub-14'),
(26, 21, 'Sub-16');

-- --------------------------------------------------------

--
-- Estrutura da tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `id_utilizador` int(11) NOT NULL,
  `id_convocatoria` int(11) DEFAULT NULL,
  `tipo` varchar(40) NOT NULL DEFAULT 'convocatoria',
  `titulo` varchar(150) NOT NULL,
  `mensagem` varchar(500) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '/convocatoria.html',
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `plantel_jogadores`
--

CREATE TABLE `plantel_jogadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `idade` varchar(30) DEFAULT NULL,
  `posicao` varchar(50) DEFAULT 'Universal',
  `escalao` varchar(30) NOT NULL,
  `foto` varchar(255) DEFAULT '../img/equipa/default.png',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `plantel_jogadores`
--

INSERT INTO `plantel_jogadores` (`id`, `nome`, `numero`, `idade`, `posicao`, `escalao`, `foto`, `ativo`, `criado_em`) VALUES
(9, 'João Francisco', '5', '18 anos', 'Lateral', 'Sub-18', '../img/equipas/joao.png', 0, '2026-06-08 08:18:45'),
(10, 'João Francisco', '5', '18 anos', 'Lateral', 'Sub-18', '../img/equipa/joao.png', 1, '2026-06-08 08:19:44'),
(11, 'Diego Brito', '6', '14 anos', 'Central', 'Sub-18', '../img/equipa/diego.png', 0, '2026-06-08 08:20:34'),
(12, 'Mateus Gago', '10', '18 anos', 'Central', 'Sub-18', '../img/equipa/mateus.png', 1, '2026-06-08 08:21:23'),
(13, 'Rafael Martin', '8', '17 anos', 'Lateral', 'Sub-18', '../img/equipa/rafael.png', 1, '2026-06-08 08:23:54'),
(14, 'Santiago Pedro', '9', '18 anos', 'Lateral', 'Sub-18', '../img/equipa/santiago_pedro.png', 1, '2026-06-08 08:24:47'),
(15, 'Stefanut Triba', '14', '15 anos', 'Lateral', 'Sub-18', '../img/equipa/stefaun.png', 0, '2026-06-08 08:25:58'),
(16, 'Stefanut Triba', '14', '15 anos', 'Lateral', 'Sub-18', '../img/equipa/sfetaun.png', 1, '2026-06-08 08:26:50'),
(17, 'Diogo Machado', '11', '18 anos', 'Pivot', 'Sub-18', '../img/equipa/diog.png', 1, '2026-06-08 08:27:47'),
(18, 'Rafael Faustino', '15', '16 anos', 'Pivot', 'Sub-18', '../img/equipa/faustino.png', 1, '2026-06-08 08:28:39'),
(19, 'Daniel Hrab', '13', '18 anos', 'Pivot', 'Sub-18', '../img/equipa/daniel.png', 1, '2026-06-08 08:29:29'),
(20, 'Miguel Dias', '20', '17 anos', 'Ponta', 'Sub-18', '../img/equipa/miguel.png', 1, '2026-06-08 08:30:20'),
(21, 'Leandro Santos', '19', '17 anos', 'Ponta', 'Sub-18', '../img/equipa/leandro.png', 1, '2026-06-08 08:31:10'),
(22, 'Salvador Cabrita', '3', '17 anos', 'Ponta', 'Sub-18', '../img/equipa/salvador.png', 1, '2026-06-08 08:32:26'),
(23, 'Leonardo Brito', '87', '18 anos', 'Guarda-redes', 'Sub-18', '../img/equipa/Leonardo.jpg', 1, '2026-06-08 08:33:23'),
(24, 'Albino Sancho', '29', '17 anos', 'Guarda-redes', 'Sub-18', '../img/equipa/Albino.png', 1, '2026-06-08 08:34:21'),
(25, 'Lourenço Brandão', '1', '18 anos', 'Guarda-redes', 'Sub-18', '../img/equipa/lourenço.png', 1, '2026-06-08 08:35:07'),
(26, 'Santiago Redondo', '4', '16 anos', 'Guarda-redes', 'Sub-18', '../img/equipa/Santiago_redondo.png', 0, '2026-06-08 08:36:33'),
(27, 'Santiago Redondo', '4', '16 anos', 'Ponta', 'Sub-18', '../img/equipa/Santiago_redondo.png', 1, '2026-06-08 08:37:32'),
(28, 'Diego Brito', '6', '14 anos', 'Universal', 'Sub-16', '../img/equipa/diego.png', 0, '2026-06-08 08:39:00'),
(29, 'Stefanut Triba', '14', '15 anos', 'Universal', 'Sub-16', '../img/equipa/sfetaun.png', 0, '2026-06-08 08:39:44'),
(30, 'Gabriel Agostinho', '33', '14 anos', 'Universal', 'Sub-16', '../img/equipa/gabriel.png', 0, '2026-06-08 08:41:39'),
(31, 'Rafael Faustino', '15', '16 anos', 'Universal', 'Sub-16', '../img/equipa/faustino.png', 0, '2026-06-08 08:42:15'),
(32, 'Santiago Redondo', '4', '16 anos', 'Universal', 'Sub-16', '../img/equipa/Santiago_redondo.png', 0, '2026-06-08 08:43:06'),
(33, 'Enzo', '11', '14 anos', 'Guarda-redes', 'Sub-16', '../img/equipa/enzo.png', 1, '2026-06-08 08:44:30'),
(34, 'Pedro Nunes', '17', '14 anos', 'Lateral', 'Sub-16', '../img/equipa/pedro.png', 1, '2026-06-08 08:45:16'),
(35, 'Stefanut Triba', '14', '15 anos', 'Lateral', 'Sub-16', '../img/equipa/sfetaun.png', 1, '2026-06-08 08:46:03'),
(36, 'Diego Brito', '6', '14 anos', 'Central', 'Sub-16', '../img/equipa/diego.png', 0, '2026-06-08 08:47:01'),
(37, 'Santiago Redondo', '4', '16 anos', 'Ponta', 'Sub-16', '../img/equipa/Santiago_redondo.png', 1, '2026-06-08 08:48:08'),
(38, 'Gabriel Agostinho', '33', '14 anos', 'Ponta', 'Sub-16', '../img/equipa/gabriel.png', 1, '2026-06-08 08:49:10'),
(39, 'Rafael Faustino', '15', '16 anos', 'Pivot', 'Sub-16', '../img/equipa/faustino.png', 1, '2026-06-08 08:50:00'),
(40, 'Henrique', '88', '14 anos', 'Pivot', 'Sub-16', '../img/equipa/henrique.png', 1, '2026-06-08 08:50:58'),
(41, 'Jamie', '67', '13 anos', 'Ponta', 'Sub-16', '../img/equipa/Jamie.png', 1, '2026-06-08 08:51:40'),
(42, 'Diego Brito', '6', '14 anos', 'Universal', 'Sub-14', '../img/equipa/diego.png', 0, '2026-06-08 08:52:59'),
(43, 'Enzo', '11', '14 anos', 'Universal', 'Sub-14', '../img/equipa/enzo.png', 1, '2026-06-08 08:53:28'),
(44, 'Pedro Nunes', '17', '14 anos', 'Universal', 'Sub-14', '../img/equipa/pedro.png', 1, '2026-06-08 08:53:57'),
(45, 'Henrique', '88', '14 anos', 'Universal', 'Sub-14', '../img/equipa/henrique.png', 1, '2026-06-08 08:54:28'),
(46, 'Jamie', '67', '13 anos', 'Universal', 'Sub-14', '../img/equipa/Jamie.png', 1, '2026-06-08 08:54:57'),
(47, 'Artem', '7', '13 anos', 'Universal', 'Sub-14', '../img/equipa/artem.png', 1, '2026-06-08 08:55:29'),
(48, 'Diego Brito', '6', '14', 'Central', 'Sub-18', '/img/equipa/uploads/atleta_20260730_161658_a10170a1fe.png', 1, '2026-07-30 15:16:58');

-- --------------------------------------------------------

--
-- Estrutura da tabela `plantel_jogador_escaloes`
--

CREATE TABLE `plantel_jogador_escaloes` (
  `id` int(11) NOT NULL,
  `id_plantel_jogador` int(11) NOT NULL,
  `escalao` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `plantel_jogador_escaloes`
--

INSERT INTO `plantel_jogador_escaloes` (`id`, `id_plantel_jogador`, `escalao`) VALUES
(21, 9, 'Sub-18'),
(22, 10, 'Sub-18'),
(23, 11, 'Sub-18'),
(24, 12, 'Sub-18'),
(25, 13, 'Sub-18'),
(26, 14, 'Sub-18'),
(27, 15, 'Sub-18'),
(28, 16, 'Sub-18'),
(29, 17, 'Sub-18'),
(30, 18, 'Sub-18'),
(31, 19, 'Sub-18'),
(32, 20, 'Sub-18'),
(33, 21, 'Sub-18'),
(34, 22, 'Sub-18'),
(35, 23, 'Sub-18'),
(36, 24, 'Sub-18'),
(37, 25, 'Sub-18'),
(38, 26, 'Sub-18'),
(39, 27, 'Sub-18'),
(7, 28, 'Sub-16'),
(8, 29, 'Sub-16'),
(9, 30, 'Sub-16'),
(10, 31, 'Sub-16'),
(11, 32, 'Sub-16'),
(12, 33, 'Sub-16'),
(13, 34, 'Sub-16'),
(14, 35, 'Sub-16'),
(15, 36, 'Sub-16'),
(16, 37, 'Sub-16'),
(17, 38, 'Sub-16'),
(18, 39, 'Sub-16'),
(19, 40, 'Sub-16'),
(20, 41, 'Sub-16'),
(1, 42, 'Sub-14'),
(2, 43, 'Sub-14'),
(3, 44, 'Sub-14'),
(4, 45, 'Sub-14'),
(5, 46, 'Sub-14'),
(6, 47, 'Sub-14'),
(66, 48, 'Sub-14'),
(65, 48, 'Sub-16'),
(64, 48, 'Sub-18');

-- --------------------------------------------------------

--
-- Estrutura da tabela `treinador_escalao`
--

CREATE TABLE `treinador_escalao` (
  `id` int(11) NOT NULL,
  `id_treinador` int(11) DEFAULT NULL,
  `escalao` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `treinador_escalao`
--

INSERT INTO `treinador_escalao` (`id`, `id_treinador`, `escalao`) VALUES
(1, 14, 'Sub-16'),
(2, 14, 'Sub-18'),
(3, 15, 'Sub-14'),
(4, 15, 'Sub-16'),
(5, 15, 'Sub-18'),
(6, 18, 'Sub-16'),
(7, 18, 'Sub-18'),
(8, 20, 'Sub-14'),
(9, 20, 'Sub-16'),
(10, 20, 'Sub-18');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telemovel` int(11) NOT NULL,
  `morada` varchar(200) NOT NULL,
  `password` varchar(150) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizadores`
--

CREATE TABLE `utilizadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('admin','jogador','encarregado','treinador') DEFAULT 'jogador',
  `nif` varchar(20) DEFAULT '',
  `morada` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `utilizadores`
--

INSERT INTO `utilizadores` (`id`, `nome`, `email`, `senha`, `tipo`, `nif`, `morada`) VALUES
(16, 'Administrador', 'admin', '$2y$10$CryiDZW8TM4rzDDPpwb5qeKMh9NrsyLXQeq/bKaDlywMRZNB1tBae', 'admin', '', 'Rua da Saudade'),
(17, 'Leonardo Brito', 'brito10055@gmail.com', '$2y$10$uGQ4oVkXs0YWycbT2BI4f.SpEbnm2USoQOJb7UAczSVzFtRx2/5J2', 'jogador', '268547852', 'R. Manuel António Pina 1'),
(18, 'Vitor Jesus', 'vitor@gmail.com', '98v47lGoDL47', 'treinador', '123987546', 'Av D. João VI'),
(19, 'Marta Brito', 'marta.brito1005@gmail.com', 'LeoDi1208', 'encarregado', '225698745', 'R. Manuel António Pina 1'),
(20, 'Mario Miguel', 'mario@gmail.com', 'Mario1990', 'treinador', '789256314', 'Tavira'),
(21, 'Diego Brito', 'diego@gmail.com', 'diego1287', 'jogador', '963456789', 'Olhão');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `convocados`
--
ALTER TABLE `convocados`
  ADD KEY `id_convocatoria` (`id_convocatoria`),
  ADD KEY `id_jogador` (`id_jogador`);

--
-- Índices para tabela `convocatorias`
--
ALTER TABLE `convocatorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_convocatorias_criador` (`id_criador`);

--
-- Índices para tabela `encarregados_jogadores`
--
ALTER TABLE `encarregados_jogadores`
  ADD PRIMARY KEY (`id_encarregado`,`id_jogador`);

--
-- Índices para tabela `faturas`
--
ALTER TABLE `faturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_jogador` (`id_jogador`);

--
-- Índices para tabela `jogador_escaloes`
--
ALTER TABLE `jogador_escaloes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_jogador` (`id_jogador`);

--
-- Índices para tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_notificacao_convocatoria_utilizador` (`id_utilizador`,`id_convocatoria`,`tipo`),
  ADD KEY `idx_notificacoes_utilizador_lida` (`id_utilizador`,`lida`),
  ADD KEY `idx_notificacoes_criado_em` (`criado_em`),
  ADD KEY `fk_notificacoes_convocatoria` (`id_convocatoria`);

--
-- Índices para tabela `plantel_jogadores`
--
ALTER TABLE `plantel_jogadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plantel_escalao` (`escalao`),
  ADD KEY `idx_plantel_ativo` (`ativo`);

--
-- Índices para tabela `plantel_jogador_escaloes`
--
ALTER TABLE `plantel_jogador_escaloes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plantel_jogador_escalao` (`id_plantel_jogador`,`escalao`),
  ADD KEY `idx_plantel_jogador_escaloes_escalao` (`escalao`);

--
-- Índices para tabela `treinador_escalao`
--
ALTER TABLE `treinador_escalao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id_treinador`) USING BTREE;

--
-- Índices para tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `convocatorias`
--
ALTER TABLE `convocatorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `faturas`
--
ALTER TABLE `faturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `jogador_escaloes`
--
ALTER TABLE `jogador_escaloes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `plantel_jogadores`
--
ALTER TABLE `plantel_jogadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `plantel_jogador_escaloes`
--
ALTER TABLE `plantel_jogador_escaloes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT de tabela `treinador_escalao`
--
ALTER TABLE `treinador_escalao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `convocados`
--
ALTER TABLE `convocados`
  ADD CONSTRAINT `convocados_ibfk_1` FOREIGN KEY (`id_convocatoria`) REFERENCES `convocatorias` (`id`),
  ADD CONSTRAINT `convocados_ibfk_2` FOREIGN KEY (`id_jogador`) REFERENCES `utilizadores` (`id`);

--
-- Limitadores para a tabela `convocatorias`
--
ALTER TABLE `convocatorias`
  ADD CONSTRAINT `fk_convocatorias_criador` FOREIGN KEY (`id_criador`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `faturas`
--
ALTER TABLE `faturas`
  ADD CONSTRAINT `faturas_ibfk_1` FOREIGN KEY (`id_jogador`) REFERENCES `utilizadores` (`id`);

--
-- Limitadores para a tabela `jogador_escaloes`
--
ALTER TABLE `jogador_escaloes`
  ADD CONSTRAINT `jogador_escaloes_ibfk_1` FOREIGN KEY (`id_jogador`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `fk_notificacoes_convocatoria` FOREIGN KEY (`id_convocatoria`) REFERENCES `convocatorias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notificacoes_utilizador` FOREIGN KEY (`id_utilizador`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `plantel_jogador_escaloes`
--
ALTER TABLE `plantel_jogador_escaloes`
  ADD CONSTRAINT `fk_plantel_jogador_escaloes_jogador` FOREIGN KEY (`id_plantel_jogador`) REFERENCES `plantel_jogadores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
