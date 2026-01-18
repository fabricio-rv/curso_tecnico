// Logica.js - VERSÃO CORRIGIDA
console.log("🚀 Carregando logica.js...")

// Aguardar DOM estar pronto
function whenReady(fn) {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", fn)
  } else {
    fn()
  }
}

// Inicialização principal
whenReady(() => {
  console.log("✅ DOM pronto, inicializando lógica...")

  initPasswordToggle()
  initMascaras()
  initFormValidation()
  initAnimations()
  initSidebar()
})

// Toggle de senha
function initPasswordToggle() {
  const toggleButtons = document.querySelectorAll("#togglePassword, [data-toggle='password']")

  toggleButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault()

      // Encontrar o input de senha relacionado
      const passwordInput =
        this.previousElementSibling ||
        document.getElementById("password") ||
        document.getElementById("senha") ||
        this.parentElement.querySelector("input[type='password'], input[type='text']")

      if (!passwordInput) {
        console.warn("❌ Input de senha não encontrado")
        return
      }

      const icon = this.querySelector("i")

      if (passwordInput.type === "password") {
        passwordInput.type = "text"
        if (icon) {
          icon.classList.remove("bi-eye")
          icon.classList.add("bi-eye-slash")
        }
      } else {
        passwordInput.type = "password"
        if (icon) {
          icon.classList.remove("bi-eye-slash")
          icon.classList.add("bi-eye")
        }
      }

      console.log("👁️ Toggle senha executado")
    })
  })
}

// Máscaras para inputs
function initMascaras() {
  // Máscara CPF
  const cpfInputs = document.querySelectorAll('input[name="cpf"], input[id="cpf"], .cpf-mask')
  cpfInputs.forEach((input) => {
    input.addEventListener("input", (e) => {
      let value = e.target.value.replace(/\D/g, "")
      value = value.replace(/(\d{3})(\d)/, "$1.$2")
      value = value.replace(/(\d{3})(\d)/, "$1.$2")
      value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2")
      e.target.value = value
    })
    console.log("✅ Máscara CPF aplicada")
  })

  // Máscara Telefone
  const telefoneInputs = document.querySelectorAll('input[name="telefone"], input[id="telefone"], .telefone-mask')
  telefoneInputs.forEach((input) => {
    input.addEventListener("input", (e) => {
      let value = e.target.value.replace(/\D/g, "")
      value = value.replace(/(\d{2})(\d)/, "($1) $2")
      value = value.replace(/(\d{5})(\d)/, "$1-$2")
      e.target.value = value
    })
    console.log("✅ Máscara telefone aplicada")
  })
}

// Validação de formulários
function initFormValidation() {
  const forms = document.querySelectorAll("form")

  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      console.log("📝 Validando formulário...")

      if (!validateForm(this)) {
        e.preventDefault()
        console.log("❌ Formulário inválido")
      } else {
        console.log("✅ Formulário válido")
      }
    })
  })
}

// Função de validação
function validateForm(form) {
  const inputs = form.querySelectorAll("input[required]")
  let isValid = true

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      showError(input, "Este campo é obrigatório")
      isValid = false
    } else {
      clearError(input)
    }
  })

  // Validação de email
  const emailInput = form.querySelector('input[type="email"]')
  if (emailInput && emailInput.value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(emailInput.value)) {
      showError(emailInput, "Email inválido")
      isValid = false
    }
  }

  // Validação de confirmação de senha
  const senhaInput = form.querySelector('input[name="senha"], input[name="nova_senha"]')
  const confirmarSenhaInput = form.querySelector('input[name="confirmar_senha"]')

  if (senhaInput && confirmarSenhaInput && confirmarSenhaInput.value) {
    if (senhaInput.value !== confirmarSenhaInput.value) {
      showError(confirmarSenhaInput, "As senhas não coincidem")
      isValid = false
    }

    if (senhaInput.value.length < 4) {
      showError(senhaInput, "A senha deve ter pelo menos 4 caracteres")
      isValid = false
    }
  }

  return isValid
}

// Mostrar erro
function showError(input, message) {
  clearError(input)

  input.classList.add("is-invalid")
  const errorDiv = document.createElement("div")
  errorDiv.className = "invalid-feedback"
  errorDiv.textContent = message

  // Inserir após o input ou após o parent se for input-group
  const parent = input.parentElement
  if (parent.classList.contains("input-group")) {
    parent.parentElement.appendChild(errorDiv)
  } else {
    parent.appendChild(errorDiv)
  }
}

// Limpar erro
function clearError(input) {
  input.classList.remove("is-invalid")
  const parent = input.parentElement

  // Procurar em diferentes locais
  let errorDiv = parent.querySelector(".invalid-feedback")
  if (!errorDiv && parent.classList.contains("input-group")) {
    errorDiv = parent.parentElement.querySelector(".invalid-feedback")
  }

  if (errorDiv) {
    errorDiv.remove()
  }
}

// Animações
function initAnimations() {
  const fadeElements = document.querySelectorAll(".fade-in")
  fadeElements.forEach((element, index) => {
    element.style.animationDelay = `${index * 0.2}s`
  })
}

// Sidebar responsivo
function initSidebar() {
  // Adicionar evento de clique no botão mobile (se existir)
  const sidebarToggle = document.querySelector(".sidebar-toggle")
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", toggleSidebar)
  }
}

function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar")
  if (sidebar) {
    sidebar.classList.toggle("show")
    console.log("📱 Sidebar toggled")
  }
}

// Função para mostrar alertas
function showAlert(message, type = "success") {
  const alertDiv = document.createElement("div")
  alertDiv.className = `alert alert-${type} alert-dismissible fade show`
  alertDiv.innerHTML = `
    <i class="bi bi-${type === "success" ? "check-circle" : "exclamation-triangle"}"></i>
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `

  const container = document.querySelector(".container, .main-content")
  if (container) {
    container.insertBefore(alertDiv, container.firstChild)

    // Auto-remover após 5 segundos
    setTimeout(() => {
      if (alertDiv.parentElement) {
        alertDiv.remove()
      }
    }, 5000)
  }
}

// Smooth scroll para links internos
whenReady(() => {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()
      const target = document.querySelector(this.getAttribute("href"))
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
        })
      }
    })
  })
})

// Funções globais
window.showAlert = showAlert
window.toggleSidebar = toggleSidebar
window.validateForm = validateForm
window.showError = showError
window.clearError = clearError

console.log("✅ logica.js carregado completamente")
