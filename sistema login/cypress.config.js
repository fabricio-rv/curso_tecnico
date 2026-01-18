const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8080/',
    supportFile: false, 
    viewportWidth: 1280,
    viewportHeight: 800,
    video: false,
    setupNodeEvents(on, config) {
      // listeners opcionais
    },
  },
});
