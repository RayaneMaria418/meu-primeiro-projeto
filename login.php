<div class="row justify-content-center">
    <div class="col-md-5">
        <h1>Entrar</h1>
        <?php
            if (!empty($_SESSION['aviso'])) {
                print "<div class='alert alert-" . h($_SESSION['aviso_tipo'] ?? 'danger') . "'>" . h($_SESSION['aviso']) . "</div>";
                unset($_SESSION['aviso'], $_SESSION['aviso_tipo']);
            }
        ?>
        <form action="?page=salvar-login" method="POST">
            <input type="hidden" name="acao" value="entrar">
            <div class="mb-3">
                <label for="login">CPF</label>
                <input type="text" id="login" name="login" class="form-control" placeholder="Somente números" required autofocus>
            </div>
            <div class="mb-3">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" required>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Entrar</button>
            </div>
        </form>
        <p>É paciente e ainda não tem acesso? <a href="?page=primeiro-acesso">Faça seu primeiro acesso</a> para agendar consultas pela internet.</p>
    </div>
</div>
