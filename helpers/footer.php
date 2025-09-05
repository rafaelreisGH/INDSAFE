<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script>
    document.getElementById("form-muda-senha").addEventListener("submit", async function(e) {
        e.preventDefault(); // evita envio tradicional do form

        const form = e.target;
        const formData = new FormData(form);
        const msgDiv = document.getElementById("resultadoSenha");
        msgDiv.innerHTML = ""; // limpa mensagem anterior

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData
            });
            const result = await response.json();

            if (result.status === "sucesso") {
                msgDiv.innerHTML = `<div class="alert alert-success">${result.mensagem}</div>`;

                // Aguarda 2 segundos para o usuário ler a mensagem
                setTimeout(() => {
                    const modalElement = document.getElementById('mudaSenhaModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modal.hide();

                    // Garante que o redirecionamento ocorra após o modal ser fechado
                    modalElement.addEventListener('hidden.bs.modal', event => {
                        window.location.href = "../Views/login.php"; // tela de login
                    }, {
                        once: true
                    }); // O listener é executado apenas uma vez

                }, 2000);

            } else {
                msgDiv.innerHTML = `<div class="alert alert-danger">${result.mensagem}</div>`;
            }

        } catch (error) {
            msgDiv.innerHTML =
                `<div class="alert alert-danger">Erro inesperado ao processar sua solicitação.</div>`;
            console.error("Erro no fetch:", error);
        }
    });
</script>

</body>

</html>