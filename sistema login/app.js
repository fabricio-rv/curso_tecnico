const form = document.getElementById('login-form');

form.addEventListener('submit', (e) => {
  e.preventDefault();

  const email = document.getElementById('email').value.trim();
  const senha = document.getElementById('senha').value.trim();

  if (!email || !senha) {
    alert('Preencha email e senha');
    return;
  }

  const usuario = { email, senha };
  localStorage.setItem('usuario', JSON.stringify(usuario));

  window.location.href = '/dashboard.html';
});
