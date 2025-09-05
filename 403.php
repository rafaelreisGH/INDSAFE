<?php
http_response_code(403);
include_once 'helpers/header_basico.php'; 
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Acesso Negado</h3>
        <div class="card">
            <div class="card-body text-center">
                <h1 class="display-1">403</h1>
                <p class="lead">Você não tem permissão para acessar esta página.</p>
                <a href="../index.php" class="btn btn-primary mt-3">Voltar para a Página Inicial</a>
            </div>
        </div>
    </div>
</main>

<?php
include_once 'helpers/footer.php'; 
?>