<?php
// Opcional: define o cabeçalho HTTP 404 para o navegador e mecanismos de busca
http_response_code(404);

// Inclui o cabeçalho básico do site.
// O caminho deve ser relativo ao 404.php na raiz do seu projeto.
include_once 'helpers/header_basico.php'; 
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Página não encontrada</h3>
        <div class="card">
            <div class="card-body text-center">
                <h1 class="display-1">404</h1>
                <p class="lead">A página que você está procurando não existe ou foi movida.</p>
                <a href="../index.php" class="btn btn-primary mt-3">Voltar para a Página Inicial</a>
            </div>
        </div>
    </div>
</main>

<?php
include_once 'helpers/footer.php'; 
?>