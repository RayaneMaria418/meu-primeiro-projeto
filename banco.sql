-- =====================================================================
-- Sistema de Gestão para Posto de Saúde
-- Banco de dados (MySQL 8 / MariaDB 10.5+)
--
-- ATENÇÃO: este script APAGA e recria o banco "postodesaude".
-- Importe pelo phpMyAdmin (aba Importar) ou pelo terminal:
--   mysql -u root -p < banco.sql
-- =====================================================================

DROP DATABASE IF EXISTS `postodesaude`;
CREATE DATABASE `postodesaude` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `postodesaude`;

-- ---------------------------------------------------------------------
-- Profissionais e usuários (Usuario > ProfissionalSaude > Medico/Enfermeiro/Farmaceutico)
-- ---------------------------------------------------------------------
CREATE TABLE `funcionario` (
  `id_funcionario` INT NOT NULL AUTO_INCREMENT,
  `nome_funcionario` VARCHAR(100) NOT NULL,
  `cpf_funcionario` CHAR(11) NOT NULL,
  `cns_funcionario` CHAR(15) NULL,
  `telefone_funcionario` VARCHAR(20) NULL,
  `email_funcionario` VARCHAR(100) NULL,
  `cargo_funcionario` ENUM('Administrador','Recepcao','Medico','Enfermeiro','Odontologo','Farmaceutico','ACS') NOT NULL,
  `registro_conselho` VARCHAR(30) NULL COMMENT 'CRM, COREN, CRO ou CRF',
  `especialidade_funcionario` VARCHAR(80) NULL,
  `ativo_funcionario` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_funcionario`),
  UNIQUE KEY `uk_funcionario_cpf` (`cpf_funcionario`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Território: microárea e família (RF03)
-- ---------------------------------------------------------------------
CREATE TABLE `microarea` (
  `id_microarea` INT NOT NULL AUTO_INCREMENT,
  `nome_microarea` VARCHAR(60) NOT NULL,
  `equipe_esf` VARCHAR(100) NOT NULL,
  `acs_id` INT NULL,
  PRIMARY KEY (`id_microarea`),
  CONSTRAINT `fk_microarea_acs` FOREIGN KEY (`acs_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

CREATE TABLE `familia` (
  `id_familia` INT NOT NULL AUTO_INCREMENT,
  `nome_familia` VARCHAR(100) NOT NULL,
  `cep_familia` CHAR(8) NOT NULL,
  `endereco_familia` VARCHAR(200) NOT NULL,
  `microarea_id` INT NOT NULL,
  PRIMARY KEY (`id_familia`),
  KEY `idx_familia_cep` (`cep_familia`),
  CONSTRAINT `fk_familia_microarea` FOREIGN KEY (`microarea_id`) REFERENCES `microarea` (`id_microarea`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Paciente (RF01) — alergias e antecedentes ficam criptografados (AES-256)
-- ---------------------------------------------------------------------
CREATE TABLE `paciente` (
  `id_paciente` INT NOT NULL AUTO_INCREMENT,
  `nome_paciente` VARCHAR(100) NOT NULL,
  `cpf_paciente` CHAR(11) NULL,
  `cns_paciente` CHAR(15) NULL COMMENT 'Cartão Nacional de Saúde (SUS)',
  `dt_nasc_paciente` DATE NOT NULL,
  `sexo_paciente` ENUM('F','M') NOT NULL,
  `telefone_paciente` VARCHAR(20) NULL,
  `email_paciente` VARCHAR(100) NULL,
  `endereco_paciente` VARCHAR(200) NULL,
  `cep_paciente` CHAR(8) NULL,
  `contato_emergencia` VARCHAR(150) NULL,
  `responsavel_nome` VARCHAR(100) NULL,
  `responsavel_cpf` CHAR(11) NULL,
  `pcd_paciente` TINYINT(1) NOT NULL DEFAULT 0,
  `alergias_paciente` TEXT NULL,
  `antecedentes_familiares` TEXT NULL,
  `consentimento_lgpd` TINYINT(1) NOT NULL DEFAULT 0,
  `familia_id` INT NULL,
  `dt_cadastro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_paciente`),
  UNIQUE KEY `uk_paciente_cpf` (`cpf_paciente`),
  UNIQUE KEY `uk_paciente_cns` (`cns_paciente`),
  CONSTRAINT `fk_paciente_familia` FOREIGN KEY (`familia_id`) REFERENCES `familia` (`id_familia`)
) ENGINE=InnoDB;

CREATE TABLE `usuario` (
  `id_usuario` INT NOT NULL AUTO_INCREMENT,
  `login_usuario` CHAR(11) NOT NULL COMMENT 'CPF',
  `senha_usuario` VARCHAR(255) NOT NULL,
  `perfil_usuario` ENUM('Administrador','Recepcao','Medico','Enfermeiro','Odontologo','Farmaceutico','ACS','Paciente') NOT NULL,
  `funcionario_id` INT NULL,
  `paciente_id` INT NULL,
  `ativo_usuario` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_acesso` DATETIME NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_usuario_login` (`login_usuario`),
  UNIQUE KEY `uk_usuario_funcionario` (`funcionario_id`),
  UNIQUE KEY `uk_usuario_paciente` (`paciente_id`),
  CONSTRAINT `fk_usuario_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuario_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Programas prioritários (RF04) e pré-natal (RF05)
-- ---------------------------------------------------------------------
CREATE TABLE `programa_paciente` (
  `id_programa` INT NOT NULL AUTO_INCREMENT,
  `paciente_id` INT NOT NULL,
  `programa` ENUM('Hipertensão','Diabetes','Pré-natal','Idoso','Puericultura') NOT NULL,
  `origem` ENUM('Manual','Automático (CID)') NOT NULL DEFAULT 'Manual',
  `dt_inclusao` DATE NOT NULL,
  PRIMARY KEY (`id_programa`),
  UNIQUE KEY `uk_programa_paciente` (`paciente_id`, `programa`),
  CONSTRAINT `fk_programa_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `gestacao` (
  `id_gestacao` INT NOT NULL AUTO_INCREMENT,
  `paciente_id` INT NOT NULL,
  `dum` DATE NOT NULL COMMENT 'Data da última menstruação',
  `dpp` DATE NOT NULL COMMENT 'Data provável do parto',
  `dpp_justificativa` TEXT NULL,
  `exames_gestacao` TEXT NULL,
  `observacoes_gestacao` TEXT NULL,
  `status_gestacao` ENUM('Em acompanhamento','Encerrada') NOT NULL DEFAULT 'Em acompanhamento',
  `dt_cadastro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_gestacao`),
  CONSTRAINT `fk_gestacao_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Escalas e ausências (RF24)
-- ---------------------------------------------------------------------
CREATE TABLE `escala` (
  `id_escala` INT NOT NULL AUTO_INCREMENT,
  `funcionario_id` INT NOT NULL,
  `dia_semana` TINYINT NOT NULL COMMENT '1 = segunda ... 7 = domingo',
  `hr_inicio` TIME NOT NULL,
  `hr_fim` TIME NOT NULL,
  PRIMARY KEY (`id_escala`),
  CONSTRAINT `fk_escala_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `ausencia` (
  `id_ausencia` INT NOT NULL AUTO_INCREMENT,
  `funcionario_id` INT NOT NULL,
  `dt_inicio` DATE NOT NULL,
  `dt_fim` DATE NOT NULL,
  `motivo` ENUM('Férias','Licença','Folga','Plantão em outra unidade','Outro') NOT NULL,
  `observacao_ausencia` VARCHAR(200) NULL,
  PRIMARY KEY (`id_ausencia`),
  CONSTRAINT `fk_ausencia_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Encaminhamentos / regulação (RF14)
-- ---------------------------------------------------------------------
CREATE TABLE `encaminhamento` (
  `id_encaminhamento` INT NOT NULL AUTO_INCREMENT,
  `paciente_id` INT NOT NULL,
  `profissional_id` INT NOT NULL,
  `atendimento_id` INT NULL,
  `especialidade` VARCHAR(80) NOT NULL,
  `resumo_clinico` TEXT NOT NULL,
  `prioridade` ENUM('Eletiva','Prioritária','Urgente') NOT NULL,
  `complexidade` ENUM('Básica','Média','Alta') NOT NULL,
  `codigo_regulacao` VARCHAR(30) NULL,
  `status_encaminhamento` ENUM('Aguardando regulação','Aprovado','Negado','Utilizado') NOT NULL DEFAULT 'Aguardando regulação',
  `dt_solicitacao` DATETIME NOT NULL,
  PRIMARY KEY (`id_encaminhamento`),
  CONSTRAINT `fk_encaminhamento_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`),
  CONSTRAINT `fk_encaminhamento_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Consulta / Agendamento (RF06, RF07, RF09, RF10)
-- ---------------------------------------------------------------------
CREATE TABLE `agendamento` (
  `id_agendamento` INT NOT NULL AUTO_INCREMENT,
  `paciente_id` INT NOT NULL,
  `funcionario_id` INT NULL COMMENT 'Profissional; vazio na demanda espontânea até a triagem',
  `dt_agendamento` DATE NOT NULL,
  `hr_agendamento` TIME NOT NULL,
  `tipo_atendimento` ENUM('Consulta médica','Consulta de enfermagem','Consulta odontológica','Consulta especializada','Pré-natal','Procedimento','Teleconsulta','Demanda espontânea') NOT NULL,
  `origem_agendamento` ENUM('Remoto','Presencial','Demanda espontânea') NOT NULL,
  `status_agendamento` ENUM('Solicitado','Confirmado','Em Triagem','Em Atendimento','Aguardando Medicamento','Finalizado','Cancelado','Ausente') NOT NULL DEFAULT 'Solicitado',
  `prioridade_agendamento` ENUM('Normal','Preferencial') NOT NULL DEFAULT 'Normal',
  `senha_agendamento` VARCHAR(10) NULL,
  `dt_checkin` DATETIME NULL,
  `encaminhamento_id` INT NULL,
  `link_teleconsulta` VARCHAR(200) NULL,
  `observacao_agendamento` VARCHAR(255) NULL,
  `dt_criacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_agendamento`),
  KEY `idx_agendamento_data` (`dt_agendamento`, `hr_agendamento`),
  KEY `idx_agendamento_status` (`status_agendamento`),
  CONSTRAINT `fk_agendamento_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`),
  CONSTRAINT `fk_agendamento_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`),
  CONSTRAINT `fk_agendamento_encaminhamento` FOREIGN KEY (`encaminhamento_id`) REFERENCES `encaminhamento` (`id_encaminhamento`)
) ENGINE=InnoDB;

CREATE TABLE `agendamento_historico` (
  `id_historico` INT NOT NULL AUTO_INCREMENT,
  `agendamento_id` INT NOT NULL,
  `status_anterior` VARCHAR(30) NULL,
  `status_novo` VARCHAR(30) NOT NULL,
  `data_hora_anterior` DATETIME NULL,
  `data_hora_nova` DATETIME NULL,
  `justificativa` VARCHAR(255) NULL,
  `usuario_id` INT NULL,
  `dt_registro` DATETIME NOT NULL,
  PRIMARY KEY (`id_historico`),
  CONSTRAINT `fk_historico_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id_agendamento`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `chamada` (
  `id_chamada` INT NOT NULL AUTO_INCREMENT,
  `agendamento_id` INT NOT NULL,
  `senha_chamada` VARCHAR(10) NOT NULL,
  `tipo_chamada` ENUM('Triagem','Consultório') NOT NULL,
  `local_chamada` VARCHAR(60) NOT NULL,
  `prioridade_chamada` ENUM('Normal','Preferencial') NOT NULL,
  `dt_chamada` DATETIME NOT NULL,
  PRIMARY KEY (`id_chamada`),
  CONSTRAINT `fk_chamada_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id_agendamento`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Triagem (RF08) e atendimento / PEP (RF02, RF15)
-- ---------------------------------------------------------------------
CREATE TABLE `triagem` (
  `id_triagem` INT NOT NULL AUTO_INCREMENT,
  `agendamento_id` INT NOT NULL,
  `enfermeiro_id` INT NOT NULL,
  `pressao_arterial` VARCHAR(10) NOT NULL,
  `glicemia` INT NULL,
  `temperatura` DECIMAL(4,1) NOT NULL,
  `peso` DECIMAL(5,2) NULL,
  `altura` DECIMAL(3,2) NULL,
  `queixa_triagem` TEXT NOT NULL,
  `classificacao_risco` ENUM('Vermelho','Laranja','Amarelo','Verde','Azul') NOT NULL,
  `dt_triagem` DATETIME NOT NULL,
  PRIMARY KEY (`id_triagem`),
  UNIQUE KEY `uk_triagem_agendamento` (`agendamento_id`),
  CONSTRAINT `fk_triagem_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id_agendamento`),
  CONSTRAINT `fk_triagem_enfermeiro` FOREIGN KEY (`enfermeiro_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

CREATE TABLE `atendimento` (
  `id_atendimento` INT NOT NULL AUTO_INCREMENT,
  `agendamento_id` INT NULL,
  `paciente_id` INT NOT NULL,
  `profissional_id` INT NOT NULL,
  `tipo_registro` ENUM('Presencial','Teleconsulta','Contato telefônico') NOT NULL DEFAULT 'Presencial',
  `dt_atendimento` DATETIME NOT NULL,
  `queixa_principal` TEXT NOT NULL,
  `diagnostico_atendimento` TEXT NULL,
  `cid_atendimento` VARCHAR(10) NULL,
  `conduta_atendimento` TEXT NULL,
  `identidade_confirmada` TINYINT(1) NOT NULL DEFAULT 0,
  `consentimento_remoto` TINYINT(1) NOT NULL DEFAULT 0,
  `finalizado` TINYINT(1) NOT NULL DEFAULT 0,
  `dt_finalizacao` DATETIME NULL,
  PRIMARY KEY (`id_atendimento`),
  UNIQUE KEY `uk_atendimento_agendamento` (`agendamento_id`),
  CONSTRAINT `fk_atendimento_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id_agendamento`),
  CONSTRAINT `fk_atendimento_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`),
  CONSTRAINT `fk_atendimento_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

ALTER TABLE `encaminhamento`
  ADD CONSTRAINT `fk_encaminhamento_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimento` (`id_atendimento`);

CREATE TABLE `atendimento_adendo` (
  `id_adendo` INT NOT NULL AUTO_INCREMENT,
  `atendimento_id` INT NOT NULL,
  `profissional_id` INT NOT NULL,
  `texto_adendo` TEXT NOT NULL,
  `dt_adendo` DATETIME NOT NULL,
  PRIMARY KEY (`id_adendo`),
  CONSTRAINT `fk_adendo_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimento` (`id_atendimento`),
  CONSTRAINT `fk_adendo_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Medicamentos, estoque por lote e movimentações (RF19, RF20)
-- ---------------------------------------------------------------------
CREATE TABLE `medicamento` (
  `id_medicamento` INT NOT NULL AUTO_INCREMENT,
  `nm_medicamento` VARCHAR(150) NOT NULL,
  `concentracao_medicamento` VARCHAR(100) NOT NULL,
  `unidade_medida` VARCHAR(45) NOT NULL,
  `tipo_item` ENUM('Medicamento','Vacina','Insumo') NOT NULL DEFAULT 'Medicamento',
  `controlado` TINYINT(1) NOT NULL DEFAULT 0,
  `dose_maxima` VARCHAR(100) NULL,
  PRIMARY KEY (`id_medicamento`),
  UNIQUE KEY `uk_medicamento` (`nm_medicamento`, `concentracao_medicamento`, `unidade_medida`)
) ENGINE=InnoDB;

CREATE TABLE `estoque` (
  `id_estoque` INT NOT NULL AUTO_INCREMENT,
  `medicamento_id` INT NOT NULL,
  `lote_estoque` VARCHAR(100) NOT NULL,
  `fabricante_estoque` VARCHAR(100) NOT NULL,
  `dt_vali_estoque` DATE NOT NULL,
  `qt_estoque` INT NOT NULL DEFAULT 0,
  `dt_entrada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_estoque`),
  UNIQUE KEY `uk_estoque_lote` (`medicamento_id`, `lote_estoque`),
  CONSTRAINT `fk_estoque_medicamento` FOREIGN KEY (`medicamento_id`) REFERENCES `medicamento` (`id_medicamento`)
) ENGINE=InnoDB;

CREATE TABLE `movimentacao_estoque` (
  `id_movimentacao` INT NOT NULL AUTO_INCREMENT,
  `estoque_id` INT NOT NULL,
  `tipo_movimentacao` ENUM('Entrada','Saída por dispensação','Saída por vacinação','Transferência','Baixa por avaria','Ajuste de inventário') NOT NULL,
  `quantidade` INT NOT NULL,
  `documento` VARCHAR(60) NULL,
  `observacao_movimentacao` VARCHAR(255) NULL,
  `usuario_id` INT NULL,
  `dt_movimentacao` DATETIME NOT NULL,
  `assinatura` CHAR(64) NULL COMMENT 'Assinatura eletrônica (HMAC-SHA256)',
  PRIMARY KEY (`id_movimentacao`),
  CONSTRAINT `fk_movimentacao_estoque` FOREIGN KEY (`estoque_id`) REFERENCES `estoque` (`id_estoque`),
  CONSTRAINT `fk_movimentacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Prescrição e dispensação (RF11, RF18)
-- ---------------------------------------------------------------------
CREATE TABLE `prescricao` (
  `id_prescricao` INT NOT NULL AUTO_INCREMENT,
  `atendimento_id` INT NOT NULL,
  `paciente_id` INT NOT NULL,
  `profissional_id` INT NOT NULL,
  `dt_emissao` DATETIME NOT NULL,
  `status_prescricao` ENUM('Pendente','Atendida','Cancelada') NOT NULL DEFAULT 'Pendente',
  `observacao_prescricao` TEXT NULL,
  PRIMARY KEY (`id_prescricao`),
  CONSTRAINT `fk_prescricao_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimento` (`id_atendimento`),
  CONSTRAINT `fk_prescricao_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`),
  CONSTRAINT `fk_prescricao_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

CREATE TABLE `prescricao_item` (
  `id_item` INT NOT NULL AUTO_INCREMENT,
  `prescricao_id` INT NOT NULL,
  `medicamento_id` INT NOT NULL,
  `posologia` VARCHAR(200) NOT NULL,
  `quantidade` INT NOT NULL,
  `duracao` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id_item`),
  CONSTRAINT `fk_item_prescricao` FOREIGN KEY (`prescricao_id`) REFERENCES `prescricao` (`id_prescricao`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_medicamento` FOREIGN KEY (`medicamento_id`) REFERENCES `medicamento` (`id_medicamento`)
) ENGINE=InnoDB;

CREATE TABLE `dispensacao` (
  `id_dispensacao` INT NOT NULL AUTO_INCREMENT,
  `prescricao_id` INT NOT NULL,
  `farmaceutico_id` INT NOT NULL,
  `dt_dispensacao` DATETIME NOT NULL,
  `receita_fisica_validada` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_dispensacao`),
  CONSTRAINT `fk_dispensacao_prescricao` FOREIGN KEY (`prescricao_id`) REFERENCES `prescricao` (`id_prescricao`),
  CONSTRAINT `fk_dispensacao_farmaceutico` FOREIGN KEY (`farmaceutico_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Exames (RF12) e atestados (RF13)
-- ---------------------------------------------------------------------
CREATE TABLE `exame` (
  `id_exame` INT NOT NULL AUTO_INCREMENT,
  `nm_exame` VARCHAR(100) NOT NULL,
  `tipo_exame` ENUM('Laboratorial','Imagem') NOT NULL,
  `alto_custo` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_exame`)
) ENGINE=InnoDB;

CREATE TABLE `solicitacao_exame` (
  `id_solicitacao` INT NOT NULL AUTO_INCREMENT,
  `atendimento_id` INT NULL,
  `paciente_id` INT NOT NULL,
  `profissional_id` INT NOT NULL,
  `exame_id` INT NOT NULL,
  `justificativa_exame` TEXT NULL,
  `dt_solicitacao` DATETIME NOT NULL,
  PRIMARY KEY (`id_solicitacao`),
  CONSTRAINT `fk_solicitacao_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimento` (`id_atendimento`),
  CONSTRAINT `fk_solicitacao_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`),
  CONSTRAINT `fk_solicitacao_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `funcionario` (`id_funcionario`),
  CONSTRAINT `fk_solicitacao_exame` FOREIGN KEY (`exame_id`) REFERENCES `exame` (`id_exame`)
) ENGINE=InnoDB;

CREATE TABLE `atestado` (
  `id_atestado` INT NOT NULL AUTO_INCREMENT,
  `atendimento_id` INT NULL,
  `paciente_id` INT NOT NULL,
  `profissional_id` INT NOT NULL,
  `tipo_atestado` ENUM('Atestado médico','Declaração de comparecimento','Laudo') NOT NULL,
  `dias_afastamento` INT NULL,
  `cid_atestado` VARCHAR(10) NULL,
  `consentimento_cid` TINYINT(1) NOT NULL DEFAULT 0,
  `texto_atestado` TEXT NULL,
  `dt_emissao` DATETIME NOT NULL,
  PRIMARY KEY (`id_atestado`),
  CONSTRAINT `fk_atestado_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimento` (`id_atendimento`),
  CONSTRAINT `fk_atestado_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`),
  CONSTRAINT `fk_atestado_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Vacinação (RF16, RF17)
-- ---------------------------------------------------------------------
CREATE TABLE `vacina_calendario` (
  `id_calendario` INT NOT NULL AUTO_INCREMENT,
  `vacina` VARCHAR(80) NOT NULL,
  `dose` VARCHAR(30) NOT NULL,
  `idade_meses` INT NOT NULL COMMENT 'Idade recomendada',
  `idade_max_meses` INT NOT NULL COMMENT 'Até quando a dose é considerada pendente',
  PRIMARY KEY (`id_calendario`)
) ENGINE=InnoDB;

CREATE TABLE `vacinacao` (
  `id_vacinacao` INT NOT NULL AUTO_INCREMENT,
  `paciente_id` INT NOT NULL,
  `calendario_id` INT NOT NULL,
  `estoque_id` INT NOT NULL COMMENT 'Lote aplicado',
  `via_administracao` ENUM('Intramuscular','Subcutânea','Intradérmica','Oral') NOT NULL,
  `dt_aplicacao` DATE NOT NULL,
  `aplicador_id` INT NOT NULL,
  PRIMARY KEY (`id_vacinacao`),
  CONSTRAINT `fk_vacinacao_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`),
  CONSTRAINT `fk_vacinacao_calendario` FOREIGN KEY (`calendario_id`) REFERENCES `vacina_calendario` (`id_calendario`),
  CONSTRAINT `fk_vacinacao_estoque` FOREIGN KEY (`estoque_id`) REFERENCES `estoque` (`id_estoque`),
  CONSTRAINT `fk_vacinacao_aplicador` FOREIGN KEY (`aplicador_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Visitas domiciliares do ACS (RF21)
-- ---------------------------------------------------------------------
CREATE TABLE `visita_acs` (
  `id_visita` INT NOT NULL AUTO_INCREMENT,
  `familia_id` INT NOT NULL,
  `acs_id` INT NOT NULL,
  `dt_visita` DATETIME NOT NULL,
  `motivo_visita` VARCHAR(150) NOT NULL,
  `pessoas_acompanhadas` VARCHAR(255) NULL,
  `condicoes_observadas` TEXT NULL,
  `situacao_familia` ENUM('Acompanhada','Em risco','Ausente') NOT NULL,
  `dt_registro` DATETIME NOT NULL,
  `dentro_prazo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Registrada em até 48h (conta para metas)',
  `registro_offline` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_visita`),
  CONSTRAINT `fk_visita_familia` FOREIGN KEY (`familia_id`) REFERENCES `familia` (`id_familia`),
  CONSTRAINT `fk_visita_acs` FOREIGN KEY (`acs_id`) REFERENCES `funcionario` (`id_funcionario`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Notificações ao paciente (RN-RF07) e auditoria (RF25)
-- ---------------------------------------------------------------------
CREATE TABLE `notificacao` (
  `id_notificacao` INT NOT NULL AUTO_INCREMENT,
  `paciente_id` INT NOT NULL,
  `mensagem` VARCHAR(500) NOT NULL,
  `lida` TINYINT(1) NOT NULL DEFAULT 0,
  `dt_notificacao` DATETIME NOT NULL,
  PRIMARY KEY (`id_notificacao`),
  CONSTRAINT `fk_notificacao_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `paciente` (`id_paciente`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `auditoria` (
  `id_auditoria` BIGINT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NULL,
  `nome_usuario` VARCHAR(100) NULL,
  `perfil_usuario` VARCHAR(30) NULL,
  `acao` VARCHAR(30) NOT NULL,
  `entidade` VARCHAR(40) NOT NULL,
  `registro_id` INT NULL,
  `paciente_id` INT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `detalhes` VARCHAR(255) NULL,
  `dt_registro` DATETIME NOT NULL,
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_data` (`dt_registro`),
  KEY `idx_auditoria_paciente` (`paciente_id`)
) ENGINE=InnoDB;

-- Log inalterável (RN-RF25): o banco recusa UPDATE e DELETE na auditoria
DELIMITER $$
CREATE TRIGGER `trg_auditoria_sem_update` BEFORE UPDATE ON `auditoria` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Registros de auditoria não podem ser alterados.';
END$$
CREATE TRIGGER `trg_auditoria_sem_delete` BEFORE DELETE ON `auditoria` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Registros de auditoria não podem ser excluídos.';
END$$
DELIMITER ;

-- =====================================================================
-- DADOS DE EXEMPLO
-- Senha inicial de todos os usuários: veja o arquivo README.md.
-- (senhas "inicial:" são convertidas para hash bcrypt no primeiro login)
-- =====================================================================
INSERT INTO `funcionario` (`id_funcionario`,`nome_funcionario`,`cpf_funcionario`,`cns_funcionario`,`telefone_funcionario`,`email_funcionario`,`cargo_funcionario`,`registro_conselho`,`especialidade_funcionario`) VALUES
(1,'Administrador do Sistema','10433218100','796001338908382','(61) 3333-0001','admin@posto.local','Administrador',NULL,NULL),
(2,'Paula Ribeiro','63794026594',NULL,'(61) 3333-0002','recepcao@posto.local','Recepcao',NULL,NULL),
(3,'Dra. Maria Santos','78161849560','793103413164752','(61) 3333-0003','maria.santos@posto.local','Medico','CRM 56789-DF','Clínica Geral'),
(4,'Carlos Eduardo','25534192879','732764835030563','(61) 3333-0004','carlos.eduardo@posto.local','Enfermeiro','COREN 12345-DF','Enfermagem'),
(5,'Ana Lima','41395376719','724238849696531','(61) 3333-0005','ana.lima@posto.local','Farmaceutico','CRF 9876-DF','Farmácia'),
(6,'Dr. Pedro Alves','28710122613','791669784801844','(61) 3333-0006','pedro.alves@posto.local','Odontologo','CRO 4321-DF','Odontologia'),
(7,'José Pereira','51462704816',NULL,'(61) 3333-0007','jose.acs@posto.local','ACS',NULL,NULL),
(8,'Dr. Roberto Nunes','95701543064','739117182278242','(61) 3333-0008','roberto.nunes@posto.local','Medico','CRM 11223-DF','Cardiologia');

INSERT INTO `microarea` (`id_microarea`,`nome_microarea`,`equipe_esf`,`acs_id`) VALUES
(1,'Microárea 01','ESF Vila Nova',7),
(2,'Microárea 02','ESF Centro',7);

INSERT INTO `familia` (`id_familia`,`nome_familia`,`cep_familia`,`endereco_familia`,`microarea_id`) VALUES
(1,'Família Silva','72000100','Quadra 10, Casa 5 - Vila Nova',1),
(2,'Família Oliveira Costa','72000200','Quadra 12, Casa 8 - Vila Nova',1),
(3,'Família Souza','72000300','Rua das Flores, 120 - Centro',2);

INSERT INTO `paciente` (`id_paciente`,`nome_paciente`,`cpf_paciente`,`cns_paciente`,`dt_nasc_paciente`,`sexo_paciente`,`telefone_paciente`,`email_paciente`,`endereco_paciente`,`cep_paciente`,`contato_emergencia`,`responsavel_nome`,`responsavel_cpf`,`pcd_paciente`,`consentimento_lgpd`,`familia_id`) VALUES
(1,'João Silva','89638346540','730103105183472','1985-03-15','M','(61) 98888-0001','joao.silva@email.com','Quadra 10, Casa 5 - Vila Nova','72000100','Ana Silva (esposa) - (61) 98888-1001',NULL,NULL,0,1,1),
(2,'Maria Oliveira','38299737648','731165667010651','1996-07-22','F','(61) 98888-0002','maria.oliveira@email.com','Quadra 12, Casa 8 - Vila Nova','72000200','Paulo Costa (companheiro) - (61) 98888-1002',NULL,NULL,0,1,2),
(3,'Antônio Souza','13338726236','747317810801323','1950-11-02','M','(61) 98888-0003',NULL,'Rua das Flores, 120 - Centro','72000300','Lúcia Souza (filha) - (61) 98888-1003',NULL,NULL,0,1,3),
(4,'Laura Oliveira Costa',NULL,'780500978820817',DATE_SUB(CURDATE(), INTERVAL 4 MONTH),'F',NULL,NULL,'Quadra 12, Casa 8 - Vila Nova','72000200','Maria Oliveira (mãe) - (61) 98888-0002','Maria Oliveira','38299737648',0,1,2),
(5,'Pedro Lima','67736026025',NULL,'2001-01-10','M','(61) 98888-0005','pedro.lima@email.com','Quadra 3, Casa 1 - Centro','72000400',NULL,NULL,NULL,0,1,NULL),
(6,'Carla Mendes','21913619354','799091699854355','1990-05-30','F','(61) 98888-0006','carla.mendes@email.com','Quadra 7, Casa 2 - Centro','72000500',NULL,NULL,NULL,1,1,NULL);

INSERT INTO `usuario` (`login_usuario`,`senha_usuario`,`perfil_usuario`,`funcionario_id`,`paciente_id`) VALUES
('10433218100','inicial:123456','Administrador',1,NULL),
('63794026594','inicial:123456','Recepcao',2,NULL),
('78161849560','inicial:123456','Medico',3,NULL),
('25534192879','inicial:123456','Enfermeiro',4,NULL),
('41395376719','inicial:123456','Farmaceutico',5,NULL),
('28710122613','inicial:123456','Odontologo',6,NULL),
('51462704816','inicial:123456','ACS',7,NULL),
('95701543064','inicial:123456','Medico',8,NULL),
('89638346540','inicial:123456','Paciente',NULL,1);

-- Escalas: segunda a sexta, 08h-12h e 13h-17h
INSERT INTO `escala` (`funcionario_id`,`dia_semana`,`hr_inicio`,`hr_fim`)
SELECT f.id_funcionario, d.dia, h.ini, h.fim
FROM funcionario f
CROSS JOIN (SELECT 1 dia UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) d
CROSS JOIN (SELECT '08:00:00' ini, '12:00:00' fim UNION SELECT '13:00:00','17:00:00') h
WHERE f.id_funcionario IN (3,4,6,8);

INSERT INTO `ausencia` (`funcionario_id`,`dt_inicio`,`dt_fim`,`motivo`,`observacao_ausencia`) VALUES
(8, DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Férias', 'Férias programadas');

INSERT INTO `programa_paciente` (`paciente_id`,`programa`,`origem`,`dt_inclusao`) VALUES
(2,'Pré-natal','Manual',DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(3,'Hipertensão','Automático (CID)',DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(3,'Idoso','Manual',DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(4,'Puericultura','Manual',DATE_SUB(CURDATE(), INTERVAL 100 DAY));

INSERT INTO `gestacao` (`paciente_id`,`dum`,`dpp`,`exames_gestacao`,`observacoes_gestacao`) VALUES
(2, DATE_SUB(CURDATE(), INTERVAL 20 WEEK), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 20 WEEK), INTERVAL 280 DAY),
 'Hemograma e tipagem sanguínea (1º trimestre) - normais', 'Segunda gestação, sem intercorrências.');

-- Catálogo de medicamentos, vacinas e insumos
INSERT INTO `medicamento` (`id_medicamento`,`nm_medicamento`,`concentracao_medicamento`,`unidade_medida`,`tipo_item`,`controlado`,`dose_maxima`) VALUES
(1,'Dipirona','500 mg','Comprimido','Medicamento',0,'Até 4 g por dia (adulto)'),
(2,'Amoxicilina','500 mg','Cápsula','Medicamento',0,'Até 3 g por dia (adulto)'),
(3,'Losartana','50 mg','Comprimido','Medicamento',0,'Até 100 mg por dia'),
(4,'Clonazepam','2 mg','Comprimido','Medicamento',1,'Até 20 mg por dia (adulto)'),
(5,'Paracetamol','750 mg','Comprimido','Medicamento',0,'Até 4 g por dia (adulto)'),
(6,'Vacina BCG','0,1 mL','Dose','Vacina',0,NULL),
(7,'Vacina Hepatite B','0,5 mL','Dose','Vacina',0,NULL),
(8,'Vacina Pentavalente','0,5 mL','Dose','Vacina',0,NULL),
(9,'Vacina VIP (Poliomielite)','0,5 mL','Dose','Vacina',0,NULL),
(10,'Vacina Pneumocócica 10-valente','0,5 mL','Dose','Vacina',0,NULL),
(11,'Vacina Rotavírus','1,5 mL','Dose','Vacina',0,NULL),
(12,'Vacina Meningocócica C','0,5 mL','Dose','Vacina',0,NULL),
(13,'Seringa descartável','3 mL','Unidade','Insumo',0,NULL);

INSERT INTO `estoque` (`id_estoque`,`medicamento_id`,`lote_estoque`,`fabricante_estoque`,`dt_vali_estoque`,`qt_estoque`) VALUES
(1,1,'DIP2026A','EMS',DATE_ADD(CURDATE(), INTERVAL 1 YEAR),500),
(2,2,'AMX2026B','Medley',DATE_ADD(CURDATE(), INTERVAL 20 DAY),200),
(3,3,'LOS2025X','Eurofarma',DATE_SUB(CURDATE(), INTERVAL 10 DAY),100),
(4,3,'LOS2027A','Eurofarma',DATE_ADD(CURDATE(), INTERVAL 1 YEAR),300),
(5,4,'CLO2027','Roche',DATE_ADD(CURDATE(), INTERVAL 1 YEAR),60),
(6,5,'PAR2027','Neo Química',DATE_ADD(CURDATE(), INTERVAL 1 YEAR),300),
(7,6,'BCG26A','Fundação Ataulpho de Paiva',DATE_ADD(CURDATE(), INTERVAL 6 MONTH),20),
(8,7,'HEPB26A','Instituto Butantan',DATE_ADD(CURDATE(), INTERVAL 8 MONTH),30),
(9,8,'PENTA26A','Serum Institute',DATE_ADD(CURDATE(), INTERVAL 8 MONTH),30),
(10,9,'VIP26A','Sanofi',DATE_ADD(CURDATE(), INTERVAL 8 MONTH),30),
(11,10,'PNEU26A','Fiocruz/Bio-Manguinhos',DATE_ADD(CURDATE(), INTERVAL 8 MONTH),30),
(12,11,'ROTA26A','GSK',DATE_ADD(CURDATE(), INTERVAL 5 MONTH),25),
(13,12,'MENC26A','Fundação Ezequiel Dias',DATE_ADD(CURDATE(), INTERVAL 8 MONTH),30),
(14,13,'SER26A','BD',DATE_ADD(CURDATE(), INTERVAL 3 YEAR),1000);

INSERT INTO `movimentacao_estoque` (`estoque_id`,`tipo_movimentacao`,`quantidade`,`documento`,`observacao_movimentacao`,`usuario_id`,`dt_movimentacao`)
SELECT id_estoque, 'Entrada', qt_estoque, 'CARGA-INICIAL', 'Carga inicial do sistema', 1, NOW() FROM estoque;

INSERT INTO `exame` (`nm_exame`,`tipo_exame`,`alto_custo`) VALUES
('Hemograma completo','Laboratorial',0),
('Glicemia de jejum','Laboratorial',0),
('Hemoglobina glicada (HbA1c)','Laboratorial',0),
('Colesterol total e frações','Laboratorial',0),
('Urina tipo 1 (EAS)','Laboratorial',0),
('Beta-HCG','Laboratorial',0),
('Eletrocardiograma','Imagem',0),
('Raio-X de tórax','Imagem',0),
('Ultrassonografia obstétrica','Imagem',0),
('Tomografia computadorizada','Imagem',1),
('Ressonância magnética','Imagem',1);

-- Subconjunto do Calendário Nacional de Vacinação (crianças e adolescentes)
INSERT INTO `vacina_calendario` (`id_calendario`,`vacina`,`dose`,`idade_meses`,`idade_max_meses`) VALUES
(1,'BCG','Dose única',0,59),
(2,'Hepatite B','Dose ao nascer',0,1),
(3,'Pentavalente','1ª dose',2,83),
(4,'Pentavalente','2ª dose',4,83),
(5,'Pentavalente','3ª dose',6,83),
(6,'VIP (Poliomielite)','1ª dose',2,59),
(7,'VIP (Poliomielite)','2ª dose',4,59),
(8,'VIP (Poliomielite)','3ª dose',6,59),
(9,'Pneumocócica 10-valente','1ª dose',2,59),
(10,'Pneumocócica 10-valente','2ª dose',4,59),
(11,'Pneumocócica 10-valente','Reforço',12,59),
(12,'Rotavírus','1ª dose',2,3),
(13,'Rotavírus','2ª dose',4,7),
(14,'Meningocócica C','1ª dose',3,59),
(15,'Meningocócica C','2ª dose',5,59),
(16,'Meningocócica C','Reforço',12,59),
(17,'Febre amarela','Dose',9,59),
(18,'Febre amarela','Reforço',48,59),
(19,'Tríplice viral','1ª dose',12,59),
(20,'Tríplice viral','2ª dose',15,59),
(21,'DTP','1º reforço',15,83),
(22,'DTP','2º reforço',48,83),
(23,'HPV','Dose única',108,179);

-- Vacinas já aplicadas na bebê Laura (as de 3 e 4 meses ficam pendentes para o alerta)
INSERT INTO `vacinacao` (`paciente_id`,`calendario_id`,`estoque_id`,`via_administracao`,`dt_aplicacao`,`aplicador_id`) VALUES
(4,1,7,'Intradérmica',DATE_SUB(CURDATE(), INTERVAL 4 MONTH),4),
(4,2,8,'Intramuscular',DATE_SUB(CURDATE(), INTERVAL 4 MONTH),4),
(4,3,9,'Intramuscular',DATE_SUB(CURDATE(), INTERVAL 2 MONTH),4),
(4,6,10,'Intramuscular',DATE_SUB(CURDATE(), INTERVAL 2 MONTH),4),
(4,9,11,'Intramuscular',DATE_SUB(CURDATE(), INTERVAL 2 MONTH),4),
(4,12,12,'Oral',DATE_SUB(CURDATE(), INTERVAL 2 MONTH),4);

-- Histórico: atendimentos anteriores
INSERT INTO `agendamento` (`id_agendamento`,`paciente_id`,`funcionario_id`,`dt_agendamento`,`hr_agendamento`,`tipo_atendimento`,`origem_agendamento`,`status_agendamento`,`prioridade_agendamento`,`senha_agendamento`) VALUES
(1,1,3,DATE_SUB(CURDATE(), INTERVAL 10 DAY),'09:00:00','Consulta médica','Remoto','Finalizado','Normal','N001'),
(2,3,3,DATE_SUB(CURDATE(), INTERVAL 2 DAY),'10:00:00','Consulta médica','Presencial','Aguardando Medicamento','Preferencial','P001');

INSERT INTO `triagem` (`agendamento_id`,`enfermeiro_id`,`pressao_arterial`,`glicemia`,`temperatura`,`peso`,`altura`,`queixa_triagem`,`classificacao_risco`,`dt_triagem`) VALUES
(1,4,'120/80',95,38.2,78.50,1.75,'Febre e dor no corpo há 2 dias','Verde',TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY),'09:10:00')),
(2,4,'160/100',110,36.5,82.00,1.68,'Dor de cabeça frequente','Amarelo',TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'10:10:00'));

INSERT INTO `atendimento` (`id_atendimento`,`agendamento_id`,`paciente_id`,`profissional_id`,`tipo_registro`,`dt_atendimento`,`queixa_principal`,`diagnostico_atendimento`,`cid_atendimento`,`conduta_atendimento`,`finalizado`,`dt_finalizacao`) VALUES
(1,1,1,3,'Presencial',TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY),'09:30:00'),'Febre e dor no corpo','Síndrome gripal','J11','Repouso, hidratação e antitérmico.',1,TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY),'09:50:00')),
(2,2,3,3,'Presencial',TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'10:30:00'),'Cefaleia e pressão alta','Hipertensão arterial sistêmica','I10','Iniciar anti-hipertensivo e retornar em 30 dias.',1,TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'10:55:00'));

INSERT INTO `prescricao` (`id_prescricao`,`atendimento_id`,`paciente_id`,`profissional_id`,`dt_emissao`,`status_prescricao`) VALUES
(1,1,1,3,TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY),'09:45:00'),'Atendida'),
(2,2,3,3,TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'10:50:00'),'Pendente');

INSERT INTO `prescricao_item` (`prescricao_id`,`medicamento_id`,`posologia`,`quantidade`,`duracao`) VALUES
(1,1,'1 comprimido a cada 6 horas se febre ou dor',12,'3 dias'),
(2,3,'1 comprimido pela manhã',30,'30 dias');

INSERT INTO `dispensacao` (`prescricao_id`,`farmaceutico_id`,`dt_dispensacao`) VALUES
(1,5,TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY),'10:05:00'));

INSERT INTO `solicitacao_exame` (`atendimento_id`,`paciente_id`,`profissional_id`,`exame_id`,`dt_solicitacao`) VALUES
(2,3,3,4,TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'10:45:00')),
(2,3,3,7,TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'10:45:00'));

-- Agendamentos de hoje e de amanhã (para testar check-in, fila, triagem e atendimento)
INSERT INTO `agendamento` (`paciente_id`,`funcionario_id`,`dt_agendamento`,`hr_agendamento`,`tipo_atendimento`,`origem_agendamento`,`status_agendamento`,`link_teleconsulta`) VALUES
(1,3,CURDATE(),'09:00:00','Consulta médica','Remoto','Confirmado',NULL),
(2,3,CURDATE(),'09:30:00','Pré-natal','Presencial','Confirmado',NULL),
(3,4,CURDATE(),'10:00:00','Consulta de enfermagem','Presencial','Confirmado',NULL),
(6,3,CURDATE(),'14:00:00','Teleconsulta','Remoto','Confirmado','https://meet.jit.si/posto-saude-teleconsulta-demo'),
(5,6,DATE_ADD(CURDATE(), INTERVAL 1 DAY),'08:00:00','Consulta odontológica','Remoto','Confirmado',NULL);

INSERT INTO `agendamento_historico` (`agendamento_id`,`status_anterior`,`status_novo`,`justificativa`,`usuario_id`,`dt_registro`)
SELECT id_agendamento, 'Solicitado', 'Confirmado', 'Agenda validada automaticamente', 1, dt_criacao FROM agendamento;

INSERT INTO `encaminhamento` (`paciente_id`,`profissional_id`,`atendimento_id`,`especialidade`,`resumo_clinico`,`prioridade`,`complexidade`,`codigo_regulacao`,`status_encaminhamento`,`dt_solicitacao`) VALUES
(3,3,2,'Cardiologia','Hipertensão de difícil controle, solicito avaliação cardiológica.','Prioritária','Média','REG-2026-000001','Aprovado',TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'10:50:00'));

INSERT INTO `visita_acs` (`familia_id`,`acs_id`,`dt_visita`,`motivo_visita`,`pessoas_acompanhadas`,`condicoes_observadas`,`situacao_familia`,`dt_registro`,`dentro_prazo`) VALUES
(3,7,TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY),'15:00:00'),'Acompanhamento de hipertenso','Antônio Souza','Orientado a retirar na farmácia da UBS a medicação prescrita; pressão aferida 150/95.','Acompanhada',TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY),'17:00:00'),1);

INSERT INTO `notificacao` (`paciente_id`,`mensagem`,`dt_notificacao`) VALUES
(1,'Sua consulta de hoje às 09:00 com Dra. Maria Santos está confirmada. Chegue 15 minutos antes com documento com foto e Cartão SUS.',NOW());
