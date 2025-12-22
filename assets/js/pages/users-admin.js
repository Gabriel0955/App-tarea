// Users Admin Page JavaScript

// Cargar tema guardado
window.addEventListener('DOMContentLoaded', function() {
  const savedTheme = localStorage.getItem('appTheme') || 'dark';
  if (savedTheme !== 'dark') {
    const themes = {
      blue: {
        '--bg-body': '#0a0e1a',
        '--bg-card': '#0f172a',
        '--bg-card-hover': '#1e293b',
        '--primary-gradient-start': '#1e3a8a',
        '--primary-gradient-end': '#0f172a'
      },
      purple: {
        '--bg-body': '#0d0a1a',
        '--bg-card': '#1e1b4b',
        '--bg-card-hover': '#2e1c5d',
        '--primary-gradient-start': '#5b21b6',
        '--primary-gradient-end': '#1e1b4b'
      },
      green: {
        '--bg-body': '#061412',
        '--bg-card': '#022c22',
        '--bg-card-hover': '#064e3b',
        '--primary-gradient-start': '#065f46',
        '--primary-gradient-end': '#022c22'
      },
      red: {
        '--bg-body': '#120b0e',
        '--bg-card': '#1f1418',
        '--bg-card-hover': '#3f1f27',
        '--primary-gradient-start': '#7f1d1d',
        '--primary-gradient-end': '#1f1418'
      },
      gray: {
        '--bg-body': '#0a0c10',
        '--bg-card': '#111827',
        '--bg-card-hover': '#1f2937',
        '--primary-gradient-start': '#374151',
        '--primary-gradient-end': '#111827'
      }
    };
    
    const theme = themes[savedTheme];
    if (theme) {
      Object.keys(theme).forEach(property => {
        document.documentElement.style.setProperty(property, theme[property]);
      });
    }
  }
});
