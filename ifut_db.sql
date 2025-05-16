-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/05/2025 às 00:02
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ifut_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `marcacoes`
--

CREATE TABLE `marcacoes` (
  `id_marcacao` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_partida` int(11) NOT NULL,
  `posicao` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `marcacoes`
--

INSERT INTO `marcacoes` (`id_marcacao`, `id_usuario`, `id_partida`, `posicao`) VALUES
(64, 4, 30, 'ALA E'),
(65, 4, 13, 'VOL'),
(66, 4, 32, 'MEI'),
(68, 6, 37, 'VOL'),
(69, 6, 38, 'ATA'),
(70, 6, 36, 'MEI');

-- --------------------------------------------------------

--
-- Estrutura para tabela `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `endereco` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `horario` time NOT NULL,
  `turno` enum('MANHÃ','TARDE','NOITE') NOT NULL,
  `posicoes_restantes` text NOT NULL,
  `posicoes_marcadas` text DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `partidas`
--

INSERT INTO `partidas` (`id_partida`, `estado`, `cidade`, `endereco`, `data`, `horario`, `turno`, `posicoes_restantes`, `posicoes_marcadas`, `id_usuario`) VALUES
(13, 'RS', 'viamao', 'rua 18', '2025-09-13', '23:00:00', 'NOITE', 'ZAG,ALA E,MEI,ATA', 'ZAG,VOL,MEI', 6),
(30, 'RS', 'Porto Alegre', 'Avenida da Cavalhada 4760 APTO 310 BL B3', '2025-05-05', '11:00:00', 'MANHÃ', 'GOL,VOL', 'GOL,ALA E,VOL', 6),
(32, 'RS', 'Porto Alegre', 'Avenid2222 APTO 310 BL B3', '2025-06-20', '11:00:00', 'NOITE', 'VOL,ATA', '', 6),
(36, 'RS', 'Porto Alegre', 'Avenida da Cavalhada 4760 APTO 310 BL B3', '2025-09-11', '09:00:00', 'MANHÃ', 'ATA,ALA E', '', 4),
(37, 'RS', 'Porto Alegre', 'Avenida da Cavalhada 4760 APTO 310 BL B3', '2025-09-11', '11:00:00', 'MANHÃ', 'ALA E,ATA', '', 4),
(38, 'RS', 'Porto Alegre', 'Avenida da Cavalhada 4760 APTO 310 BL B3', '2025-09-11', '22:00:00', 'NOITE', 'GOL,ZAG,ALA E,VOL', '', 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(15) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `senha` varchar(15) NOT NULL,
  `posicao` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nome`, `email`, `telefone`, `cpf`, `senha`, `posicao`) VALUES
(4, 'Fabricio Rassier', 'fa@gmail.com', '51996628041', '01851878095', '123456', 'ATA'),
(5, 'Fabricio Pedro', 'aa@gmail.com', '51996628041', '01851878099', '123456', 'GOL'),
(6, 'lolo', 'lo@gmail.com', '5199887898', '01851878060', '123456', 'ALA DIR');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `marcacoes`
--
ALTER TABLE `marcacoes`
  ADD PRIMARY KEY (`id_marcacao`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_partida` (`id_partida`);

--
-- Índices de tabela `partidas`
--
ALTER TABLE `partidas`
  ADD PRIMARY KEY (`id_partida`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `marcacoes`
--
ALTER TABLE `marcacoes`
  MODIFY `id_marcacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `marcacoes`
--
ALTER TABLE `marcacoes`
  ADD CONSTRAINT `marcacoes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `marcacoes_ibfk_2` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id_partida`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
