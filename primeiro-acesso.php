<div class="row justify-content-center">
    <div class="col-md-6">
        <h1>Primeiro acesso do paciente</h1>
        <?php
            if (!empty($_SESSION['aviso'])) {
                print "<div class='alert alert-" . h($_SESSION['aviso_tipo'] ?? 'danger') . "'>" . h($_SESSION['aviso']) . "</div>";
                unset($_SESSION['aviso'], $_SESSION['aviso_tipo']);
            }
        ?>
        <p>Se você já tem cadastro na UBS, crie sua senha para agendar consultas, acompanhar seu histórico e ver seu cartão de vacina pela internet.</p>
        <form action="?page=salvar-login" method="POST">
            <input type="hidden" name="acao" value="primeiro-acesso">
            <div class="mb-3">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="dt_nasc">Data de nascimento</label>
                <input type="date" id="dt_nasc" name="dt_nasc" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="senha">Crie uma senha (mínimo 6 caracteres)</label>
                <input type="password" id="senha" name="senha" class="form-control" minlength="6" required>
            </div>
            <div class="mb-3">
                <label for="senha2">Repita a senha</label>
                <input type="password" id="senha2" name="senha2" class="form-control" minlength="6" required>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Criar acesso</button>
                <a href="?page=login" class="btn btn-secondary">Voltar</a>
            </div>
        </form>
    </div>
</div>
