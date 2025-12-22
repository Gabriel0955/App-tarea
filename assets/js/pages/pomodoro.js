// Pomodoro Page JavaScript

let timerInterval = null;
let timeRemaining = 25 * 60; // segundos
let isWorking = true;
let isPaused = false;
let sessionStartTime = null;
let currentSessionId = null;

// Sonido de notificación (usando Web Audio API)
function playNotificationSound() {
  const audioContext = new (window.AudioContext || window.webkitAudioContext)();
  const oscillator = audioContext.createOscillator();
  const gainNode = audioContext.createGain();
  
  oscillator.connect(gainNode);
  gainNode.connect(audioContext.destination);
  
  oscillator.frequency.value = 800;
  oscillator.type = 'sine';
  gainNode.gain.value = 0.3;
  
  oscillator.start();
  setTimeout(() => oscillator.stop(), 200);
}

function formatTime(seconds) {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function updateDisplay() {
  document.getElementById('timerDisplay').textContent = formatTime(timeRemaining);
}

function startTimer() {
  if (timerInterval) return;
  
  isPaused = false;
  sessionStartTime = new Date();
  
  const workDuration = parseInt(document.getElementById('workDuration').value);
  const breakDuration = parseInt(document.getElementById('breakDuration').value);
  
  if (timeRemaining === workDuration * 60 || timeRemaining === breakDuration * 60) {
    // Nueva sesión
    timeRemaining = isWorking ? workDuration * 60 : breakDuration * 60;
  }
  
  document.getElementById('startBtn').style.display = 'none';
  document.getElementById('pauseBtn').style.display = 'inline-block';
  document.getElementById('timerCard').classList.add('active');
  
  timerInterval = setInterval(() => {
    timeRemaining--;
    updateDisplay();
    
    if (timeRemaining <= 0) {
      completeTimer();
    }
  }, 1000);
}

function pauseTimer() {
  if (!timerInterval) return;
  
  clearInterval(timerInterval);
  timerInterval = null;
  isPaused = true;
  
  document.getElementById('startBtn').style.display = 'inline-block';
  document.getElementById('pauseBtn').style.display = 'none';
  document.getElementById('timerCard').classList.remove('active');
}

function resetTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
  isPaused = false;
  
  const workDuration = parseInt(document.getElementById('workDuration').value);
  timeRemaining = workDuration * 60;
  isWorking = true;
  
  updateDisplay();
  document.getElementById('timerLabel').textContent = '🍅 Pomodoro';
  document.getElementById('startBtn').style.display = 'inline-block';
  document.getElementById('pauseBtn').style.display = 'none';
  document.getElementById('timerCard').classList.remove('active');
}

function completeTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
  
  playNotificationSound();
  document.getElementById('timerCard').classList.remove('active');
  
  if (isWorking) {
    // Completó un Pomodoro
    savePomodoro('completed');
    showNotification('¡Pomodoro Completado!', '¡Excelente trabajo! Toma un descanso. 🎉');
    
    // Cambiar a descanso
    isWorking = false;
    const breakDuration = parseInt(document.getElementById('breakDuration').value);
    timeRemaining = breakDuration * 60;
    document.getElementById('timerLabel').textContent = '☕ Descanso';
    updateDisplay();
    
    document.getElementById('startBtn').style.display = 'inline-block';
    document.getElementById('pauseBtn').style.display = 'none';
  } else {
    // Completó descanso
    showNotification('Descanso Terminado', '¡Listo para otro Pomodoro! 💪');
    
    // Volver a trabajo
    isWorking = true;
    const workDuration = parseInt(document.getElementById('workDuration').value);
    timeRemaining = workDuration * 60;
    document.getElementById('timerLabel').textContent = '🍅 Pomodoro';
    updateDisplay();
    
    document.getElementById('startBtn').style.display = 'inline-block';
    document.getElementById('pauseBtn').style.display = 'none';
  }
}

async function savePomodoro(status) {
  const taskId = document.getElementById('taskSelect').value || null;
  const workDuration = parseInt(document.getElementById('workDuration').value);
  
  try {
    const response = await fetch('pomodoro_save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        task_id: taskId,
        work_duration: workDuration,
        status: status
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Mostrar logros desbloqueados
      if (data.achievements && data.achievements.length > 0) {
        data.achievements.forEach((ach, index) => {
          setTimeout(() => showAchievement(ach), index * 3000);
        });
      }
      
      // Recargar después de 3 segundos
      setTimeout(() => location.reload(), 3000);
    }
  } catch (error) {
    console.error('Error al guardar Pomodoro:', error);
  }
}

function showNotification(title, message) {
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification(title, {
      body: message,
      icon: '../../assets/icon-192x192.png',
      badge: '../../assets/icon-72x72.png'
    });
  }
}

function showAchievement(achievement) {
  const notification = document.getElementById('achievementNotification');
  document.getElementById('achievementIcon').textContent = achievement.icon;
  document.getElementById('achievementTitle').textContent = '🎉 ' + achievement.name;
  document.getElementById('achievementDesc').textContent = achievement.description + ' (+' + achievement.points + ' pts)';
  
  notification.classList.add('show');
  playNotificationSound();
  
  setTimeout(() => {
    notification.classList.remove('show');
  }, 5000);
}

// Solicitar permisos de notificación
if ('Notification' in window && Notification.permission === 'default') {
  Notification.requestPermission();
}

// Advertir antes de cerrar si hay sesión activa
window.addEventListener('beforeunload', (e) => {
  if (timerInterval && !isPaused) {
    e.preventDefault();
    e.returnValue = '¿Seguro que quieres salir? Perderás el progreso de tu Pomodoro actual.';
  }
});

// Inicializar
updateDisplay();
