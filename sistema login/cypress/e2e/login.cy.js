/// <reference types="cypress" />

describe('Login - E2E', () => {
  beforeEach(() => {
    cy.clearLocalStorage()
    cy.visit('/')
  })

  it('Deve cadastrar e redirecionar para o dashboard', () => {
    cy.get('#email').type('teste@teste.com')
    cy.get('#senha').type('123456')
    cy.get('#submit-btn').click()

    // Verifica se foi redirecionado
    cy.url().should('include', '/dashboard.html')

    // Confere se o usuário foi salvo
    cy.window().then((win) => {
      const user = JSON.parse(win.localStorage.getItem('usuario'))
      expect(user.email).to.eq('teste@teste.com')
    })
  })

  it('Deve também redirecionar corretamente ao dashboard em outra tentativa', () => {
    cy.get('#email').type('usuario2@teste.com')
    cy.get('#senha').type('abcdef')
    cy.get('#submit-btn').click()

    cy.url().should('include', '/dashboard.html')

    cy.window().then((win) => {
      const user = JSON.parse(win.localStorage.getItem('usuario'))
      expect(user.email).to.eq('usuario2@teste.com')
    })
  })
})
