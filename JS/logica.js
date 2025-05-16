// Alternar exibição de senhas 👁️
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = this.previousElementSibling;
        const tipo = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', tipo);
        this.textContent = tipo === 'password' ? '👁️' : '🙈';
    });
});

// Validação simples para formulários 🛡️
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function (e) {
        let validado = true;

        // Valida todos os campos obrigatórios
        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(campo => {
            if (!campo.value.trim()) {
                campo.classList.add('is-invalid');
                validado = false;
            } else {
                campo.classList.remove('is-invalid');
            }
        });

        if (!validado) {
            e.preventDefault();
            alert("⚠️ Preencha todos os campos obrigatórios antes de continuar.");
        }
    });
});

// Marcar posições (para páginas como criar_partida.php) ⚽
window.togglePosition = function (element) {
    element.classList.toggle("selected");

    const selecionadas = [];
    document.querySelectorAll('.position-box.selected').forEach(el => {
        selecionadas.push(el.dataset.posicao);
    });

    const inputHidden = document.getElementById("posicoesSelecionadas");
    if (inputHidden) {
        inputHidden.value = selecionadas.join(',');
    }
};

// Máscaras de CPF e Telefone 🧼
document.addEventListener("DOMContentLoaded", function () {
    const cpfInput = document.querySelector('input[name="cpf"]');
    const telInput = document.querySelector('input[name="telefone"]');

    if (cpfInput) {
        cpfInput.addEventListener("input", function () {
            let v = this.value.replace(/\D/g, "").slice(0, 11);
            v = v.replace(/(\d{3})(\d)/, "$1.$2");
            v = v.replace(/(\d{3})(\d)/, "$1.$2");
            v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            this.value = v;
        });
    }

    if (telInput) {
        telInput.addEventListener("input", function () {
            let v = this.value.replace(/\D/g, "").slice(0, 11);
            v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
            v = v.replace(/(\d{5})(\d{1,4})$/, "$1-$2");
            this.value = v;
        });
    }
});