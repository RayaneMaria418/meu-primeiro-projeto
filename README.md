# Sistema de Gestão para Posto de Saúde

Protótipo da APS (UDF, Análise e Projeto de Sistemas II, 2025.2) em PHP + MySQL + Bootstrap 5.
O visual é o do projeto original; o backend foi refeito para cobrir, de forma simples, os 25 requisitos funcionais do documento.

## Como rodar (XAMPP)

1. Instale o [XAMPP](https://www.apachefriends.org/) e inicie **Apache** e **MySQL** no painel.
2. Copie esta pasta para `C:\xampp\htdocs\postodesaude`.
3. Abra `http://localhost/phpmyadmin`, vá em **Importar** e envie o arquivo `banco.sql`.
   O script apaga e recria o banco `postodesaude` com dados de exemplo.
4. Acesse `http://localhost/postodesaude`.

A conexão fica em `config.php` (padrão do XAMPP: usuário `root`, sem senha).

## Usuários de teste

Todos usam a senha inicial **123456** e entram com o CPF (só números).
No primeiro login, a senha é convertida automaticamente para hash bcrypt.

| Perfil | Nome | CPF (login) |
|---|---|---|
| Administrador (e Regulação) | Administrador do Sistema | 10433218100 |
| Recepção | Paula Ribeiro | 63794026594 |
| Médica (Clínica Geral) | Dra. Maria Santos | 78161849560 |
| Médico (Cardiologia) | Dr. Roberto Nunes | 95701543064 |
| Enfermeiro | Carlos Eduardo | 25534192879 |
| Farmacêutica | Ana Lima | 41395376719 |
| Odontólogo | Dr. Pedro Alves | 28710122613 |
| Agente Comunitário (ACS) | José Pereira | 51462704816 |
| Paciente | João Silva | 89638346540 |

Outros pacientes podem criar o acesso em **"Faça seu primeiro acesso"**, informando CPF e data de nascimento.
Exemplo: Maria Oliveira, CPF 38299737648, nascida em 22/07/1996.

## Roteiro rápido de demonstração (fluxo dos diagramas)

1. **Paciente** (João) → *Agendar Consulta* → escolhe tipo, profissional, dia e horário (agendamento remoto).
2. **Recepção** → *Listar Agendamentos* (hoje) → **Check-in** gera a senha (preferencial para idosos, gestantes e PCD).
3. **Enfermeiro** → *Fila de Atendimento* → **Chamar próximo** (aparece no *Painel de Chamada*) → **Triagem** com sinais vitais e classificação de risco.
4. **Médica** → *Fila* → **Chamar próximo** → **Atender**: diagnóstico/CID, receita, exames, atestado e encaminhamento → **Finalizar e assinar**.
5. **Farmacêutica** → *Dispensação* → CPF do paciente → confirma a entrega (baixa automática no estoque) → o agendamento fica **Finalizado**.

## Onde está cada requisito

| RF | Onde | Regra de negócio implementada |
|---|---|---|
| RF01 Cadastrar paciente | Pacientes > Novo / Listar | CPF ou CNS válido; recém-nascido vinculado ao CPF do responsável; consentimento LGPD |
| RF02 Prontuário (PEP) | Pacientes > Listar > Prontuário | Atendimento finalizado não é alterado, só adendo; só assina quem tem registro no conselho |
| RF03 Família e microárea | Pacientes > Famílias / Microáreas | Sugere a microárea pelo CEP |
| RF04 Programas prioritários | Pacientes > Programas | Inclusão automática pelo CID (hipertensão, diabetes, gestação) |
| RF05 Pré-natal | Pacientes > Pré-Natal | DPP = DUM + 280 dias; alterar a DPP exige fundamentação |
| RF06 Agendar | Agendamentos > Novo / área do paciente | Consulta especializada exige encaminhamento aprovado |
| RF07 Cancelar/remanejar | Agendamentos > Editar | Justificativa e histórico; o posto só altera com 24 h de antecedência; o paciente é notificado |
| RF08 Triagem | Fila > Triagem | Vermelho/Laranja passam na frente e geram alerta |
| RF09 Filas e painel | Fila / Painel de Chamada | Intercala 2 preferenciais para 1 normal; aviso sonoro |
| RF10 Demanda espontânea | Agendamentos > Demanda Espontânea | Vai direto para o acolhimento no mesmo dia |
| RF11 Prescrição | Atendimento > Prescrever | Controlado só com estoque e confirmação da dose máxima |
| RF12 Exames | Atendimento > Solicitar exames | Alto custo exige justificativa |
| RF13 Atestados | Atendimento > Emitir atestado | CID só com consentimento do paciente |
| RF14 Encaminhamentos | Atendimento > Encaminhar / Agendamentos > Encaminhamentos | Média/alta complexidade gera código de rastreio |
| RF15 Teleconsulta | Atendimentos > Teleconsulta | Exige identidade confirmada e aceite do termo |
| RF16 Cartão de vacina | Prontuário > Registrar vacina | Valida lote, fabricante e validade; baixa a dose do estoque |
| RF17 Vacinas atrasadas | Prontuário / Atendimentos > Vacinas Atrasadas | Compara a idade com o calendário cadastrado |
| RF18 Dispensação | Estoque > Dispensação | Controlado exige receita física com até 30 dias |
| RF19 Estoque | Estoque > Listar / Movimentações | Toda movimentação gera comprovante com assinatura eletrônica |
| RF20 Lote e validade | Estoque > Listar (filtros) e tela inicial | Bloqueia saída de lote vencido; alerta 30 dias antes |
| RF21 Visitas do ACS | Visitas | Conta para as metas só se registrada em até 48 h; funciona offline |
| RF22 Relatórios | Gestão > Relatórios | Sem nomes de pacientes; exporta CSV e imprime em PDF |
| RF23 e-SUS | Gestão > Exportação e-SUS | Só exporta fichas com CPF/CNS válidos; lista as pendências |
| RF24 Escalas | Funcionários > Escala | Bloqueia agendamento em férias, licenças e folgas |
| RF25 Auditoria | Gestão > Auditoria | Registra quem viu ou alterou o quê, com IP; o banco impede alterar ou apagar o log |

Requisitos não funcionais atendidos no código: perfis de acesso (RNF02), senhas com bcrypt e alergias/antecedentes com AES-256 (RNF03), opção de forçar HTTPS e modo de manutenção em `config.php` (RN-RNF03 e RN-RNF05), layout responsivo do Bootstrap (RNF10) e registro offline das visitas (RNF06).
Itens como 99,5% de disponibilidade e TLS 1.3 dependem do servidor onde o sistema for publicado.

## Arquivos principais

- `index.php`: layout, menus por perfil e rotas (`?page=...`).
- `config.php`: conexão com o banco e opções.
- `funcoes.php`: funções compartilhadas (banco, permissões, validações, agenda, filas, estoque, auditoria).
- `banco.sql`: estrutura do banco e dados de exemplo.
- `documentacao/`: documento da APS corrigido (`.docx`) e a lista de alterações.
