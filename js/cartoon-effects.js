// Cartoon Confetti Effect
function launchConfetti(targetSelector) {
  const target = document.querySelector(targetSelector);
  if (!target) return;
  for (let i = 0; i < 30; i++) {
    const confetti = document.createElement('div');
    confetti.className = 'confetti';
    confetti.style.left = Math.random() * 100 + '%';
    confetti.style.background = `hsl(${Math.random()*360}, 90%, 65%)`;
    confetti.style.animationDuration = (Math.random() * 1 + 1.5) + 's';
    target.appendChild(confetti);
    setTimeout(() => confetti.remove(), 2000);
  }
}

// Accessibility: Toggle cartoon animations
function toggleCartoonAnimations(enable) {
  document.body.classList.toggle('cartoon-animations-disabled', !enable);
}

// Example: Attach to a button
// document.getElementById('order-btn').addEventListener('click', () => launchConfetti('body'));

// CSS for confetti (add to cartoon-style.css):
// .confetti {
//   position: absolute;
//   top: 0;
//   width: 12px;
//   height: 12px;
//   border-radius: 50%;
//   opacity: 0.8;
//   pointer-events: none;
//   animation: confetti-fall 2s linear forwards;
//   z-index: 9999;
// }
// @keyframes confetti-fall {
//   0% { transform: translateY(-20px) scale(1); }
//   100% { transform: translateY(100vh) scale(0.7); opacity: 0; }
// } 