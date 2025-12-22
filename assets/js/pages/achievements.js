// Achievements Page JavaScript

function filterCategory(category) {
  // Actualizar tabs activos
  document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.classList.remove('active');
  });
  event.target.classList.add('active');

  // Mostrar/ocultar secciones
  const sections = document.querySelectorAll('.category-section');
  const cards = document.querySelectorAll('.achievement-card');

  if (category === 'all') {
    sections.forEach(s => s.style.display = 'block');
    cards.forEach(c => c.style.display = 'block');
  } else if (category === 'unlocked') {
    sections.forEach(s => s.style.display = 'block');
    cards.forEach(card => {
      card.style.display = card.dataset.unlocked === 'true' ? 'block' : 'none';
    });
  } else if (category === 'locked') {
    sections.forEach(s => s.style.display = 'block');
    cards.forEach(card => {
      card.style.display = card.dataset.unlocked === 'false' ? 'block' : 'none';
    });
  } else {
    sections.forEach(section => {
      section.style.display = section.dataset.category === category ? 'block' : 'none';
    });
  }
}

// Animación suave en scroll
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.category-section').forEach(section => {
  section.style.opacity = '0';
  section.style.transform = 'translateY(20px)';
  section.style.transition = 'all 0.5s ease';
  observer.observe(section);
});
