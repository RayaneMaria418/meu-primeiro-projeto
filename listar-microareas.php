<h1>Microáreas e equipes ESF</h1>
<?php if (tem_perfil(ADM)) { ?>
<form action="?page=salvar-microarea" method="POST" class="row g-2 mb-4">
	<input type="hidden" name="acao" value="cadastrar">
	<div class="col-md-3"><input type="text" name="nome" class="form-control" placeholder="Nome (ex.: Microárea 03)" required></div>
	<div class="col-md-4"><input type="text" name="equipe" class="form-control" placeholder="Equipe ESF responsável" required></div>
	<div class="col-md-3"><?php print select_profissionais('acs_id', null, [ACS], false); ?></div>
	<div class="col-md-2"><button type="submit" class="btn btn-primary">Cadastrar</button></div>
</form>
<?php } ?>
<?php
$lista = db_lista("SELECT m.*, f.nome_funcionario, (SELECT COUNT(*) FROM familia x WHERE x.microarea_id = m.id_microarea) AS familias
                   FROM microarea m LEFT JOIN funcionario f ON f.id_funcionario = m.acs_id ORDER BY m.nome_microarea");
if ($lista) {
    print "<table class='table table-bordered table-striped table-hover'>";
    print "<tr><th>#</th><th>Microárea</th><th>Equipe ESF</th><th>ACS responsável</th><th>Famílias</th>" . (tem_perfil(ADM) ? "<th>Ações</th>" : '') . "</tr>";
    foreach ($lista as $m) {
        print "<tr><td>{$m->id_microarea}</td><td>" . h($m->nome_microarea) . "</td><td>" . h($m->equipe_esf) . "</td><td>" . h($m->nome_funcionario) . "</td><td>{$m->familias}</td>";
        if (tem_perfil(ADM)) {
            print "<td><button onclick=\"if(confirm('Tem certeza que deseja excluir?')){ location.href='?page=salvar-microarea&acao=excluir&id={$m->id_microarea}'; }\" class='btn btn-danger'>Excluir</button></td>";
        }
        print "</tr>";
    }
    print "</table>";
} else {
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
