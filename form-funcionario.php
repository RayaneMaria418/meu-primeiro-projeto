<?php
// Campos do formulário de funcionário (novo-funcionario.php e editar-funcionario.php). Espera $row (ou null)
$v = fn($campo) => h($row->$campo ?? '');
?>
	<div class="mb-3">
		<label>Nome</label>
		<input type="text" name="nome" value="<?php print $v('nome_funcionario'); ?>" class="form-control" required>
	</div>
	<div class="row">
		<div class="col-md-4 mb-3">
			<label>CPF (usado como login)</label>
			<input type="text" name="cpf" value="<?php print $v('cpf_funcionario'); ?>" class="form-control" required>
		</div>
		<div class="col-md-4 mb-3">
			<label>CNS do profissional</label>
			<input type="text" name="cns" value="<?php print $v('cns_funcionario'); ?>" class="form-control">
		</div>
		<div class="col-md-4 mb-3">
			<label>Cargo / perfil de acesso</label>
			<select name="cargo" class="form-control" required>
				<option value="">-- Selecione --</option>
				<?php foreach (array_diff(array_keys(NOMES_PERFIL), [PAC]) as $c) print "<option value='$c' " . sel($c, $row->cargo_funcionario ?? '') . ">" . h(NOMES_PERFIL[$c]) . "</option>"; ?>
			</select>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4 mb-3">
			<label>Registro no conselho (CRM, COREN, CRO, CRF)</label>
			<input type="text" name="registro" value="<?php print $v('registro_conselho'); ?>" class="form-control" placeholder="Ex.: CRM 56789-DF">
		</div>
		<div class="col-md-4 mb-3">
			<label>Especialidade</label>
			<input type="text" name="especialidade" value="<?php print $v('especialidade_funcionario'); ?>" class="form-control" placeholder="Ex.: Clínica Geral">
		</div>
		<div class="col-md-4 mb-3">
			<label>Telefone</label>
			<input type="text" name="telefone" value="<?php print $v('telefone_funcionario'); ?>" class="form-control">
		</div>
	</div>
	<div class="row">
		<div class="col-md-6 mb-3">
			<label>E-mail</label>
			<input type="email" name="email" value="<?php print $v('email_funcionario'); ?>" class="form-control">
		</div>
		<div class="col-md-6 mb-3">
			<label><?php print empty($row) ? 'Senha de acesso' : 'Nova senha (deixe em branco para manter)'; ?></label>
			<input type="password" name="senha" class="form-control" minlength="6" <?php print empty($row) ? 'required' : ''; ?>>
		</div>
	</div>
	<?php if (!empty($row)) { ?>
	<div class="form-check mb-3">
		<input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo" <?php print marcado($row->ativo_funcionario); ?>>
		<label class="form-check-label" for="ativo">Ativo (desmarque para bloquear o acesso sem apagar o histórico)</label>
	</div>
	<?php } ?>
