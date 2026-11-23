<?php
include("config.php");
include("funcoes.php");

// RN-RNF03: em produção, só aceita HTTPS
if (FORCAR_HTTPS && empty($_SERVER['HTTPS']) && ($_SERVER['HTTP_HOST'] ?? '') !== 'localhost') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

$page = $_REQUEST["page"] ?? '';

// Rotas: página => [arquivo, perfis que podem acessar]
// (null = qualquer usuário logado; 'publico' = não precisa de login)
$rotas = [
    // acesso
    'login'                => ['login.php', 'publico'],
    'salvar-login'         => ['salvar-login.php', 'publico'],
    'primeiro-acesso'      => ['primeiro-acesso.php', 'publico'],
    'painel'               => ['painel.php', 'publico'],
    // pacientes (RF01, RF02, RF03, RF04, RF05)
    'novo'                 => ['novo-paciente.php', [ADM, REC]],
    'listar'               => ['listar-pacientes.php', [ADM, REC, MED, ENF, ODO]],
    'salvar'               => ['salvar-paciente.php', [ADM, REC, MED, ENF, ODO]],
    'editar'               => ['editar-paciente.php', [ADM, REC]],
    'pesquisar'            => ['pesquisar.php', [ADM, REC, MED, ENF, ODO, FAR]],
    'prontuario'           => ['prontuario.php', CLINICOS],
    'listar-microareas'    => ['listar-microareas.php', [ADM, REC, ACS]],
    'salvar-microarea'     => ['salvar-microarea.php', [ADM]],
    'listar-familias'      => ['listar-familias.php', [ADM, REC, ACS]],
    'nova-familia'         => ['nova-familia.php', [ADM, REC, ACS]],
    'vincular-familia'     => ['vincular-familia.php', [ADM, REC, ACS]],
    'salvar-familia'       => ['salvar-familia.php', [ADM, REC, ACS]],
    'programas'            => ['programas.php', CLINICOS],
    'salvar-programa'      => ['salvar-programa.php', CLINICOS],
    'listar-gestantes'     => ['listar-gestantes.php', [MED, ENF]],
    'nova-gestacao'        => ['nova-gestacao.php', [MED, ENF]],
    'editar-gestacao'      => ['editar-gestacao.php', [MED, ENF]],
    'salvar-gestacao'      => ['salvar-gestacao.php', [MED, ENF]],
    // funcionários e escalas (RF24)
    'novo-funcionario'     => ['novo-funcionario.php', [ADM]],
    'listar-funcionarios'  => ['listar-funcionarios.php', [ADM]],
    'salvar-funcionario'   => ['salvar-funcionario.php', [ADM]],
    'editar-funcionario'   => ['editar-funcionario.php', [ADM]],
    'escala'               => ['escala.php', [ADM]],
    'salvar-escala'        => ['salvar-escala.php', [ADM]],
    // agendamentos, filas e triagem (RF06 a RF10)
    'novo-agendamento'     => ['novo-agendamento.php', [ADM, REC, PAC]],
    'listar-agendamentos'  => ['listar-agendamentos.php', [ADM, REC, MED, ENF, ODO, PAC]],
    'salvar-agendamento'   => ['salvar-agendamento.php', [ADM, REC, PAC, MED, ENF, ODO]],
    'editar-agendamento'   => ['editar-agendamento.php', [ADM, REC, PAC]],
    'demanda-espontanea'   => ['demanda-espontanea.php', [ADM, REC]],
    'fila'                 => ['fila.php', [ADM, REC, MED, ENF, ODO]],
    'salvar-fila'          => ['salvar-fila.php', [MED, ENF, ODO]],
    'nova-triagem'         => ['nova-triagem.php', [ENF]],
    'salvar-triagem'       => ['salvar-triagem.php', [ENF]],
    // atendimento clínico (RF02, RF11 a RF15)
    'novo-atendimento'     => ['novo-atendimento.php', CLINICOS],
    'listar-atendimentos'  => ['listar-atendimentos.php', CLINICOS],
    'salvar-atendimento'   => ['salvar-atendimento.php', CLINICOS],
    'editar-atendimento'   => ['editar-atendimento.php', CLINICOS],
    'teleconsulta'         => ['teleconsulta.php', [MED, ENF]],
    'nova-prescricao'      => ['nova-prescricao.php', [MED, ODO]],
    'salvar-prescricao'    => ['salvar-prescricao.php', [MED, ODO]],
    'novo-exame'           => ['novo-exame.php', [MED, ENF]],
    'salvar-exame'         => ['salvar-exame.php', [MED, ENF]],
    'novo-atestado'        => ['novo-atestado.php', [MED, ODO]],
    'salvar-atestado'      => ['salvar-atestado.php', [MED, ODO]],
    'novo-encaminhamento'  => ['novo-encaminhamento.php', [MED]],
    'listar-encaminhamentos' => ['listar-encaminhamentos.php', [ADM, MED, REC]],
    'salvar-encaminhamento'  => ['salvar-encaminhamento.php', [ADM, MED]],
    'documento'            => ['documento.php', [MED, ENF, ODO, FAR, PAC]],
    // vacinação (RF16, RF17)
    'nova-vacinacao'       => ['nova-vacinacao.php', [ENF]],
    'salvar-vacinacao'     => ['salvar-vacinacao.php', [ENF]],
    'cartao-vacina'        => ['cartao-vacina.php', [MED, ENF, PAC]],
    'vacinas-atrasadas'    => ['vacinas-atrasadas.php', [MED, ENF]],
    // farmácia e estoque (RF18 a RF20)
    'dispensacao'          => ['dispensacao.php', [FAR]],
    'salvar-dispensacao'   => ['salvar-dispensacao.php', [FAR]],
    'novo-estoque'         => ['novo-estoque.php', [ADM, FAR]],
    'listar-estoque'       => ['listar-estoque.php', [ADM, FAR]],
    'salvar-estoque'       => ['salvar-estoque.php', [ADM, FAR]],
    'editar-estoque'       => ['editar-estoque.php', [ADM, FAR]],
    'movimentar-estoque'   => ['movimentar-estoque.php', [ADM, FAR]],
    'listar-movimentacoes' => ['listar-movimentacoes.php', [ADM, FAR]],
    'comprovante'          => ['comprovante.php', [ADM, FAR]],
    // visitas domiciliares (RF21)
    'nova-visita'          => ['nova-visita.php', [ACS]],
    'listar-visitas'       => ['listar-visitas.php', [ADM, ACS, ENF, MED]],
    'salvar-visita'        => ['salvar-visita.php', [ACS]],
    // gestão (RF22, RF23, RF25)
    'relatorios'           => ['relatorios.php', [ADM, MED, ENF, ODO, FAR]],
    'exportar-esus'        => ['exportar-esus.php', [ADM]],
    'auditoria'            => ['auditoria.php', [ADM]],
    // área do paciente
    'area-paciente'        => ['area-paciente.php', [PAC]],
    'meu-historico'        => ['meu-historico.php', [PAC]],
];

// Arquivos para download (precisam ser enviados antes de qualquer HTML)
$downloads = [
    'relatorio-csv' => ['relatorios.php', [ADM, MED, ENF, ODO, FAR]],
    'esus-xml'      => ['exportar-esus.php', [ADM]],
];
if (isset($downloads[$page]) && usuario() && tem_perfil($downloads[$page][1])) {
    define('MODO_DOWNLOAD', true);
    include($downloads[$page][0]);
    exit;
}

// Login/logout e painel de chamada (tela própria, para a TV da recepção) não usam o layout
if ($page === 'salvar-login') {
    include('salvar-login.php');
    exit;
}
if ($page === 'painel') {
    include('painel.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php print NOME_SISTEMA; ?></title>

    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <style>@media print { nav, .no-print { display: none !important; } }</style>
</head>
<body>

    <nav class="navbar navbar-expand-xl" style="background-color: #e3f2fd;" data-bs-theme="light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><?php print NOME_SISTEMA; ?></a>
            <?php if (usuario()) { ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Home</a>
                    </li>

                    <?php if (tem_perfil(PAC)) { ?>
                    <li class="nav-item"><a class="nav-link" href="?page=novo-agendamento">Agendar Consulta</a></li>
                    <li class="nav-item"><a class="nav-link" href="?page=listar-agendamentos">Meus Agendamentos</a></li>
                    <li class="nav-item"><a class="nav-link" href="?page=meu-historico">Meu Histórico</a></li>
                    <li class="nav-item"><a class="nav-link" href="?page=cartao-vacina&id=<?php print id_paciente_logado(); ?>">Cartão de Vacina</a></li>
                    <?php } ?>

                    <?php if (tem_perfil(ADM, REC, MED, ENF, ODO, ACS)) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Pacientes
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (tem_perfil(ADM, REC)) { ?><li><a class="dropdown-item" href="?page=novo">Novo Paciente</a></li><?php } ?>
                            <?php if (tem_perfil(ADM, REC, MED, ENF, ODO)) { ?><li><a class="dropdown-item" href="?page=listar">Listar Pacientes</a></li><?php } ?>
                            <?php if (tem_perfil(ADM, REC, ACS)) { ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?page=listar-familias">Famílias</a></li>
                            <li><a class="dropdown-item" href="?page=listar-microareas">Microáreas</a></li>
                            <?php } ?>
                            <?php if (tem_perfil(CLINICOS)) { ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?page=programas">Programas Prioritários</a></li>
                            <?php } ?>
                            <?php if (tem_perfil(MED, ENF)) { ?><li><a class="dropdown-item" href="?page=listar-gestantes">Pré-Natal (Gestantes)</a></li><?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if (tem_perfil(ADM)) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Funcionários
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?page=novo-funcionario">Novo Funcionário</a></li>
                            <li><a class="dropdown-item" href="?page=listar-funcionarios">Listar Funcionários e Escalas</a></li>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if (tem_perfil(ADM, REC, MED, ENF, ODO)) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Agendamentos
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (tem_perfil(ADM, REC)) { ?>
                            <li><a class="dropdown-item" href="?page=novo-agendamento">Novo Agendamento</a></li>
                            <li><a class="dropdown-item" href="?page=demanda-espontanea">Demanda Espontânea</a></li>
                            <?php } ?>
                            <li><a class="dropdown-item" href="?page=listar-agendamentos">Listar Agendamentos</a></li>
                            <li><a class="dropdown-item" href="?page=fila">Fila de Atendimento</a></li>
                            <li><a class="dropdown-item" href="?page=painel" target="_blank">Painel de Chamada</a></li>
                            <?php if (tem_perfil(ADM, REC, MED)) { ?><li><a class="dropdown-item" href="?page=listar-encaminhamentos">Encaminhamentos (Regulação)</a></li><?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if (tem_perfil(CLINICOS)) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Atendimentos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?page=fila">Fila (Triagem / Consultório)</a></li>
                            <?php if (tem_perfil(MED, ENF)) { ?><li><a class="dropdown-item" href="?page=teleconsulta">Teleconsulta</a></li><?php } ?>
                            <li><a class="dropdown-item" href="?page=listar-atendimentos">Histórico</a></li>
                            <?php if (tem_perfil(MED, ENF)) { ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?page=vacinas-atrasadas">Vacinas Atrasadas</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if (tem_perfil(ADM, FAR)) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Estoque
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (tem_perfil(FAR)) { ?><li><a class="dropdown-item" href="?page=dispensacao">Dispensação de Medicamentos</a></li><?php } ?>
                            <li><a class="dropdown-item" href="?page=novo-estoque">Novo Medicamento</a></li>
                            <li><a class="dropdown-item" href="?page=listar-estoque">Listar Estoque</a></li>
                            <li><a class="dropdown-item" href="?page=listar-movimentacoes">Movimentações</a></li>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if (tem_perfil(ACS)) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Visitas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?page=nova-visita">Nova Visita</a></li>
                            <li><a class="dropdown-item" href="?page=listar-visitas">Listar Visitas</a></li>
                        </ul>
                    </li>
                    <?php } ?>

                    <?php if (tem_perfil(ADM, MED, ENF, ODO, FAR)) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Gestão
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?page=relatorios">Relatórios de Produção</a></li>
                            <?php if (tem_perfil(ADM, MED, ENF)) { ?><li><a class="dropdown-item" href="?page=listar-visitas">Visitas Domiciliares</a></li><?php } ?>
                            <?php if (tem_perfil(ADM)) { ?>
                            <li><a class="dropdown-item" href="?page=exportar-esus">Exportação e-SUS</a></li>
                            <li><a class="dropdown-item" href="?page=auditoria">Auditoria</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php } ?>

                </ul>
                <?php if (tem_perfil(ADM, REC, MED, ENF, ODO, FAR)) { ?>
                <form class="d-flex" action="index.php" method="POST">
                    <input type="hidden" name="page" value="pesquisar">
                    <input class="form-control me-2" type="search" name="termo" placeholder="Pesquisar Paciente" aria-label="Search">
                    <button class="btn btn-outline-success" type="submit">Pesquisar</button>
                </form>
                <?php } ?>
                <ul class="navbar-nav ms-xl-2">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php print h(nome_curto(usuario()['nome'])); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><?php print h(usuario()['nome']); ?><br><small class="text-muted"><?php print h(NOMES_PERFIL[perfil()]); ?></small></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?page=salvar-login&acao=sair">Sair</a></li>
                        </ul>
                    </li>
                </ul>

            </div>
            <?php } ?>
        </div>
    </nav>
    <div class="container mt-3">
        <div class="row">
            <div class="col">
                <?php
                    if (MODO_MANUTENCAO && !tem_perfil(ADM) && !in_array($page, ['login', 'salvar-login'], true)) {
                        print "<div class='alert alert-warning'>O sistema está em manutenção programada. Tente novamente mais tarde.</div>";
                    } elseif (!usuario() && !(isset($rotas[$page]) && $rotas[$page][1] === 'publico')) {
                        include("login.php");
                    } elseif (isset($rotas[$page])) {
                        [$arquivo, $perfis] = $rotas[$page];
                        if ($perfis === 'publico' || $perfis === null || tem_perfil($perfis)) {
                            include($arquivo);
                        } else {
                            print "<div class='alert alert-danger'>Seu perfil não tem permissão para acessar esta página.</div>";
                        }
                    } elseif (tem_perfil(PAC)) {
                        include("area-paciente.php");
                    } else {
                        include("inicio.php");
                    }
                ?>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
