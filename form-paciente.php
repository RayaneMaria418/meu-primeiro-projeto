<?php
// Campos do formulário de paciente (usado em novo-paciente.php e editar-paciente.php)
// Espera $pac (objeto ou null)
$v = fn($campo) => h($pac->$campo ?? '');
?>
<div class="row">
    <div class="col-md-8 mb-3">
        <label>Nome</label>
        <input type="text" name="nome" value="<?php print $v('nome_paciente'); ?>" class="form-control" required>
    </div>
    <div class="col-md-4 mb-3">
        <label>Data de nascimento</label>
        <input type="date" name="data_nasc" value="<?php print $v('dt_nasc_paciente'); ?>" class="form-control" required>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label>CPF</label>
        <input type="text" name="cpf" value="<?php print $v('cpf_paciente'); ?>" class="form-control" placeholder="Somente números">
    </div>
    <div class="col-md-4 mb-3">
        <label>Cartão SUS (CNS)</label>
        <input type="text" name="cns" value="<?php print $v('cns_paciente'); ?>" class="form-control" placeholder="15 dígitos">
    </div>
    <div class="col-md-4 mb-3">
        <label>Sexo</label>
        <select name="sexo" class="form-control" required>
            <option value="">-- Selecione --</option>
            <option value="F" <?php print sel('F', $pac->sexo_paciente ?? ''); ?>>Feminino</option>
            <option value="M" <?php print sel('M', $pac->sexo_paciente ?? ''); ?>>Masculino</option>
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label>Telefone</label>
        <input type="text" name="telefone" value="<?php print $v('telefone_paciente'); ?>" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label>E-mail</label>
        <input type="email" name="email" value="<?php print $v('email_paciente'); ?>" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label>Contato de emergência</label>
        <input type="text" name="contato_emergencia" value="<?php print $v('contato_emergencia'); ?>" class="form-control" placeholder="Nome e telefone">
    </div>
</div>
<div class="row">
    <div class="col-md-8 mb-3">
        <label>Endereço</label>
        <input type="text" name="endereco" value="<?php print $v('endereco_paciente'); ?>" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label>CEP</label>
        <input type="text" name="cep" value="<?php print $v('cep_paciente'); ?>" class="form-control">
    </div>
</div>
<div class="mb-3">
    <label>Família / Microárea</label>
    <select name="familia_id" class="form-control">
        <option value="">-- Ainda não vinculado (vincular depois em Famílias) --</option>
        <?php
            foreach (db_lista("SELECT f.id_familia, f.nome_familia, f.cep_familia, m.nome_microarea FROM familia f
                               JOIN microarea m ON m.id_microarea = f.microarea_id ORDER BY f.nome_familia") as $f) {
                print "<option value='{$f->id_familia}' " . sel($f->id_familia, $pac->familia_id ?? '') . ">"
                    . h("{$f->nome_familia} - CEP {$f->cep_familia} - {$f->nome_microarea}") . "</option>";
            }
        ?>
    </select>
</div>
<fieldset class="border rounded p-3 mb-3">
    <legend class="fs-6">Responsável legal (obrigatório para recém-nascido sem CPF/Cartão SUS)</legend>
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="recem_nascido" value="1" id="rn" <?php print marcado(!empty($pac->id_paciente) && empty($pac->cpf_paciente) && empty($pac->cns_paciente)); ?>>
        <label class="form-check-label" for="rn">Recém-nascido sem documentação (vincular ao CPF do responsável)</label>
    </div>
    <div class="row">
        <div class="col-md-8 mb-3">
            <label>Nome do responsável</label>
            <input type="text" name="responsavel_nome" value="<?php print $v('responsavel_nome'); ?>" class="form-control">
        </div>
        <div class="col-md-4 mb-3">
            <label>CPF do responsável</label>
            <input type="text" name="responsavel_cpf" value="<?php print $v('responsavel_cpf'); ?>" class="form-control">
        </div>
    </div>
</fieldset>
<div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="pcd" value="1" id="pcd" <?php print marcado($pac->pcd_paciente ?? 0); ?>>
    <label class="form-check-label" for="pcd">Pessoa com deficiência (atendimento preferencial)</label>
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="consentimento_lgpd" value="1" id="lgpd" required <?php print marcado($pac->consentimento_lgpd ?? 0); ?>>
    <label class="form-check-label" for="lgpd">O paciente (ou responsável) autoriza o tratamento dos seus dados de saúde pela UBS, conforme a LGPD.</label>
</div>
