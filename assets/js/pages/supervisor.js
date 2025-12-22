// Supervisor Team Management JavaScript

// Modal Management
function openAddMemberModal() {
  const modal = document.getElementById('addMemberModal');
  if (modal) {
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
  }
}

function closeAddMemberModal() {
  const modal = document.getElementById('addMemberModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('addMemberForm').reset();
  }
}

function openNotesModal(memberId, memberName, currentNotes = '') {
  const modal = document.getElementById('notesModal');
  if (modal) {
    // Set member info
    document.getElementById('notesMemberId').value = memberId;
    document.getElementById('notesMemberName').textContent = memberName;
    document.getElementById('memberNotes').value = currentNotes;
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Focus textarea
    document.getElementById('memberNotes').focus();
  }
}

function closeNotesModal() {
  const modal = document.getElementById('notesModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('notesForm').reset();
  }
}

// Team Member Actions
function viewMemberTasks(memberId, memberName) {
  window.location.href = `member_tasks.php?member_id=${memberId}`;
}

function removeMember(memberId, memberName) {
  if (confirm(`¿Estás seguro de que deseas remover a ${memberName} de tu equipo?\n\nEsto no eliminará al usuario, solo lo quitará de tu equipo de supervisión.`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/supervisor_api.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'remove_member';
    
    const memberInput = document.createElement('input');
    memberInput.type = 'hidden';
    memberInput.name = 'member_id';
    memberInput.value = memberId;
    
    form.appendChild(actionInput);
    form.appendChild(memberInput);
    document.body.appendChild(form);
    form.submit();
  }
}

// Form Validation
function validateAddMemberForm() {
  const memberId = document.getElementById('member_id').value;
  if (!memberId) {
    alert('Por favor selecciona un miembro del equipo.');
    return false;
  }
  return true;
}

function validateNotesForm() {
  const notes = document.getElementById('memberNotes').value.trim();
  if (notes.length > 1000) {
    alert('Las notas no pueden exceder 1000 caracteres.');
    return false;
  }
  return true;
}

// Notifications
function showNotification(message, type = 'success') {
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === 'success' ? '#4caf50' : '#f44336'};
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 10000;
    animation: slideIn 0.3s ease;
  `;
  
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Parse URL parameters for notifications
function checkUrlParams() {
  const urlParams = new URLSearchParams(window.location.search);
  const success = urlParams.get('success');
  const error = urlParams.get('error');
  
  if (success) {
    showNotification(decodeURIComponent(success), 'success');
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (error) {
    showNotification(decodeURIComponent(error), 'error');
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

// Progress Bar Animation
function animateProgressBars() {
  const progressBars = document.querySelectorAll('.progress-fill');
  progressBars.forEach(bar => {
    const width = bar.style.width;
    bar.style.width = '0%';
    setTimeout(() => {
      bar.style.width = width;
    }, 100);
  });
}

// Member Card Hover Effects
function initMemberCards() {
  const memberCards = document.querySelectorAll('.member-card');
  memberCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-4px)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
    });
  });
}

// Close modals on outside click
function initModalOutsideClick() {
  window.onclick = function(event) {
    const addModal = document.getElementById('addMemberModal');
    const notesModal = document.getElementById('notesModal');
    
    if (event.target === addModal) {
      closeAddMemberModal();
    }
    if (event.target === notesModal) {
      closeNotesModal();
    }
  };
}

// Close modals on ESC key
function initModalEscapeKey() {
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeAddMemberModal();
      closeNotesModal();
    }
  });
}

// Search/Filter Members (optional enhancement)
function filterMembers(searchTerm) {
  const memberCards = document.querySelectorAll('.member-card');
  const lowerSearch = searchTerm.toLowerCase();
  
  memberCards.forEach(card => {
    const memberName = card.querySelector('.member-info h3').textContent.toLowerCase();
    if (memberName.includes(lowerSearch)) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

// Sort Members (optional enhancement)
function sortMembers(criteria) {
  const grid = document.querySelector('.team-members-grid');
  const cards = Array.from(document.querySelectorAll('.member-card'));
  
  cards.sort((a, b) => {
    switch(criteria) {
      case 'name':
        const nameA = a.querySelector('.member-info h3').textContent;
        const nameB = b.querySelector('.member-info h3').textContent;
        return nameA.localeCompare(nameB);
      
      case 'level':
        const levelA = parseInt(a.dataset.level || 0);
        const levelB = parseInt(b.dataset.level || 0);
        return levelB - levelA;
      
      case 'points':
        const pointsA = parseInt(a.dataset.points || 0);
        const pointsB = parseInt(b.dataset.points || 0);
        return pointsB - pointsA;
      
      case 'pending':
        const pendingA = parseInt(a.dataset.pending || 0);
        const pendingB = parseInt(b.dataset.pending || 0);
        return pendingB - pendingA;
      
      default:
        return 0;
    }
  });
  
  // Re-append sorted cards
  cards.forEach(card => grid.appendChild(card));
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  checkUrlParams();
  animateProgressBars();
  initMemberCards();
  initModalOutsideClick();
  initModalEscapeKey();
  
  console.log('Supervisor Team Management initialized');
});

// Export functions for use in HTML
window.supervisorFunctions = {
  openAddMemberModal,
  closeAddMemberModal,
  openNotesModal,
  closeNotesModal,
  viewMemberTasks,
  removeMember,
  validateAddMemberForm,
  validateNotesForm,
  filterMembers,
  sortMembers
};
