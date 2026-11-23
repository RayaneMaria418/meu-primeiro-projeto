<?php
// Painel eletrônico de chamada (RF09 / UC09). Aberto na TV da recepção, atualiza sozinho.
// Por sigilo (LGPD), mostra só a senha e o primeiro nome do paciente.
$chamadas = db_lista("SELECT c.*, p.nome_paciente FROM chamada c
                      JOIN agendamento a ON a.id_agendamento = c.agendamento_id
                      JOIN paciente p ON p.id_paciente = a.paciente_id
                      WHERE DATE(c.dt_chamada) = ? ORDER BY c.dt_chamada DESC, c.id_chamada DESC LIMIT 6", 's', hoje());
$atual = $chamadas[0] ?? null;
$primeiro_nome = fn($nome) => explode(' ', trim($nome))[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="5">
    <title>Painel de Chamada</title>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
</head>
<body style="background-color: #e3f2fd;">
    <div class="container py-4 text-center">
        <h1 class="mb-4"><?php print NOME_SISTEMA; ?> — Painel de Chamada</h1>
        <?php if ($atual) { ?>
            <div class="card mb-4">
                <div class="card-body">
                    <p class="fs-3 mb-0">Senha</p>
                    <p class="display-1 fw-bold"><?php print h($atual->senha_chamada); ?></p>
                    <p class="display-6"><?php print h($primeiro_nome($atual->nome_paciente)); ?></p>
                    <p class="fs-2"><?php print h($atual->local_chamada); ?></p>
                </div>
            </div>
            <h3>Últimas chamadas</h3>
            <table class="table table-bordered table-striped bg-white fs-4">
                <tr><th>Senha</th><th>Paciente</th><th>Local</th><th>Horário</th></tr>
                <?php foreach (array_slice($chamadas, 1) as $c) { ?>
                    <tr><td><?php print h($c->senha_chamada); ?></td><td><?php print h($primeiro_nome($c->nome_paciente)); ?></td>
                        <td><?php print h($c->local_chamada); ?></td><td><?php print date('H:i', strtotime($c->dt_chamada)); ?></td></tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="fs-3">Nenhuma chamada realizada hoje.</p>
        <?php } ?>
    </div>
    <script>
        // Aviso sonoro quando uma nova senha é chamada
        (function () {
            var atual = <?php print json_encode($atual ? $atual->id_chamada : 0); ?>;
            var anterior = 0;
            try { anterior = parseInt(sessionStorage.getItem('ultimaChamada') || '0', 10); sessionStorage.setItem('ultimaChamada', atual); } catch (e) {}
            if (atual && atual !== anterior && anterior !== 0) {
                try {
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    [0, 0.35].forEach(function (t) {
                        var o = ctx.createOscillator(); o.frequency.value = 880; o.connect(ctx.destination);
                        o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + 0.25);
                    });
                } catch (e) {}
            }
        })();
    </script>
</body>
</html>
