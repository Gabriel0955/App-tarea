// Edit Task Page JavaScript

function toggleDocuments() {
  const checkbox = document.getElementById('requiresDocs');
  const section = document.getElementById('documentsSection');
  
  if (checkbox.checked) {
    section.style.display = 'block';
  } else {
    section.style.display = 'none';
    // Desmarcar todos los documentos si se deselecciona
    document.querySelectorAll('#documentsSection input[type="checkbox"]').forEach(cb => cb.checked = false);
  }
}
