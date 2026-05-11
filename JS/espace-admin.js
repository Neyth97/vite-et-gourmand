document.querySelectorAll('.ea-nav-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.ea-nav-link').forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    document.querySelectorAll('.ea-section').forEach(s => s.style.display = 'none');
    document.getElementById('section-' + link.dataset.section).style.display = 'block';
  });
});

document.querySelectorAll('[data-modal]').forEach(el => {
  el.addEventListener('click', () => {
    const modal = document.getElementById(el.dataset.modal);
    modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
  });
});
