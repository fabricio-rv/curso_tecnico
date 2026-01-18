// Campo de Futebol Interativo - VERSÃO CORRIGIDA
class CampoFutebol {
  constructor(containerId, options = {}) {
    console.log("🚀 Inicializando CampoFutebol:", containerId, options)

    this.container = document.getElementById(containerId)
    if (!this.container) {
      console.error("❌ Container não encontrado:", containerId)
      return null
    }

    this.options = {
      multiSelect: options.multiSelect || false,
      readOnly: options.readOnly || false,
      showLegenda: options.showLegenda !== false, // Por padrão true, mas pode ser desabilitada
      posicoesSelecionadas: options.posicoesSelecionadas || [],
      posicoesDisponiveis: options.posicoesDisponiveis || [],
      posicoesOcupadas: options.posicoesOcupadas || [],
      posicoesMarcadas: options.posicoesMarcadas || [],
      onChange: options.onChange || (() => {}),
      ...options,
    }

    // Posições do campo
    this.posicoes = {
      GOL: { nome: "Goleiro", classe: "pos-gol" },
      ZAG: { nome: "Zagueiro", classe: "pos-zag" },
      "ALA ESQ": { nome: "Ala Esquerdo", classe: "pos-ala-esq" },
      "ALA DIR": { nome: "Ala Direito", classe: "pos-ala-dir" },
      VOL: { nome: "Volante", classe: "pos-vol" },
      MEI: { nome: "Meia", classe: "pos-mei" },
      ATA: { nome: "Atacante", classe: "pos-ata" },
    }

    this.selecionadas = [...this.options.posicoesSelecionadas]
    this.init()
  }

  init() {
    try {
      this.render()
      this.attachEvents()
      console.log("✅ Campo inicializado com sucesso")
    } catch (error) {
      console.error("❌ Erro ao inicializar campo:", error)
    }
  }

  render() {
    const campo = document.createElement("div")
    campo.className = "campo-futebol"

    // Criar posições
    Object.keys(this.posicoes).forEach((codigo) => {
      const posicao = this.createPosicao(codigo)
      campo.appendChild(posicao)
    })

    this.container.innerHTML = ""
    this.container.appendChild(campo)

    // Adicionar legenda apenas se habilitada e não for readonly
    if (!this.options.readOnly && this.options.showLegenda) {
      this.container.appendChild(this.createLegenda())
    }
  }

  createPosicao(codigo) {
    const div = document.createElement("div")
    const posicao = this.posicoes[codigo]

    div.className = `posicao-jogador ${posicao.classe}`
    div.dataset.posicao = codigo
    div.innerHTML = `<div class="posicao-label">${posicao.nome}</div>`

    // Definir estado da posição
    this.setEstadoPosicao(div, codigo)

    return div
  }

  setEstadoPosicao(div, codigo) {
    // Remover todas as classes de estado primeiro
    div.classList.remove("selecionada", "disponivel", "marcada", "indisponivel")

    // AZUL - Selecionada pelo usuário (prioridade máxima)
    if (this.selecionadas.includes(codigo)) {
      div.classList.add("selecionada")
      return
    }

    // VERMELHA - Já marcada por outro jogador
    if (this.options.posicoesOcupadas.includes(codigo)) {
      div.classList.add("marcada")
      return
    }

    // VERDE - Disponível para seleção
    if (this.options.posicoesDisponiveis.includes(codigo)) {
      div.classList.add("disponivel")
      return
    }

    // CINZA - Não disponível
    div.classList.add("indisponivel")
  }

  createLegenda() {
    const legenda = document.createElement("div")
    legenda.className = "campo-legenda"

    const items = [
      { classe: "selecionada", texto: "Sua Posição" },
      { classe: "disponivel", texto: "Disponível" },
      { classe: "marcada", texto: "Ocupada" },
      { classe: "indisponivel", texto: "Indisponível" },
    ]

    items.forEach((item) => {
      const div = document.createElement("div")
      div.className = "legenda-item"
      div.innerHTML = `
        <div class="legenda-cor legenda-${item.classe}"></div>
        <span>${item.texto}</span>
      `
      legenda.appendChild(div)
    })

    return legenda
  }

  attachEvents() {
    if (this.options.readOnly) return

    // Aguardar um pouco para garantir que o DOM está pronto
    setTimeout(() => {
      const posicoes = this.container.querySelectorAll(".posicao-jogador")
      console.log("🎯 Anexando eventos a", posicoes.length, "posições")

      posicoes.forEach((posicao) => {
        posicao.addEventListener("click", (e) => {
          e.preventDefault()
          e.stopPropagation()

          const codigo = posicao.dataset.posicao
          console.log("🎯 CLIQUE NA POSIÇÃO:", codigo)

          // Verificar se pode ser clicada
          if (posicao.classList.contains("marcada") || posicao.classList.contains("indisponivel")) {
            console.log("❌ Posição não clicável!")
            return
          }

          this.togglePosicao(codigo)
        })

        // Adicionar cursor pointer para posições clicáveis
        if (!posicao.classList.contains("marcada") && !posicao.classList.contains("indisponivel")) {
          posicao.style.cursor = "pointer"
        }
      })
    }, 100)
  }

  togglePosicao(codigo) {
    console.log("🔄 Toggle posição:", codigo)

    const index = this.selecionadas.indexOf(codigo)

    if (index > -1) {
      // Remover seleção
      this.selecionadas.splice(index, 1)
      console.log("➖ Removeu:", codigo)
    } else {
      // Adicionar seleção
      if (!this.options.multiSelect) {
        this.selecionadas = [codigo]
        console.log("1️⃣ Seleção única:", codigo)
      } else {
        this.selecionadas.push(codigo)
        console.log("➕ Adicionou:", codigo)
      }
    }

    // Atualizar visual
    this.updateVisual()

    // Chamar callback
    try {
      this.options.onChange(this.selecionadas)
      console.log("📞 onChange chamado com:", this.selecionadas)
    } catch (error) {
      console.error("❌ Erro no onChange:", error)
    }
  }

  updateVisual() {
    const posicoes = this.container.querySelectorAll(".posicao-jogador")
    posicoes.forEach((pos) => {
      const codigo = pos.dataset.posicao
      this.setEstadoPosicao(pos, codigo)
    })
  }

  // Métodos públicos
  getSelecionadas() {
    return [...this.selecionadas]
  }

  setSelecionadas(posicoes) {
    this.selecionadas = [...posicoes]
    this.updateVisual()
  }

  setDisponiveis(posicoes) {
    this.options.posicoesDisponiveis = [...posicoes]
    this.updateVisual()
  }

  setOcupadas(posicoes) {
    this.options.posicoesOcupadas = [...posicoes]
    this.updateVisual()
  }

  setMarcadas(posicoes) {
    this.options.posicoesMarcadas = [...posicoes]
    this.updateVisual()
  }
}

// Função helper global
function initCampoFutebol(containerId, options = {}) {
  console.log("🚀 initCampoFutebol chamada:", containerId, options)

  // Aguardar o DOM estar pronto
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      return new CampoFutebol(containerId, options)
    })
  } else {
    return new CampoFutebol(containerId, options)
  }
}

// Tornar disponível globalmente
window.CampoFutebol = CampoFutebol
window.initCampoFutebol = initCampoFutebol

console.log("✅ campo-futebol.js carregado")
