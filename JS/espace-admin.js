document.querySelectorAll('.ee-nav-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.ee-nav-link').forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    document.querySelectorAll('.ee-section').forEach(s => s.style.display = 'none');
    document.getElementById('section-' + link.dataset.section).style.display = 'block';
  });
});

document.querySelectorAll('[data-modal]').forEach(el => {
  el.addEventListener('click', () => {
    const modal = document.getElementById(el.dataset.modal);
    modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
  });
});
