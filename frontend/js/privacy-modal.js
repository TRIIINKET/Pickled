// Privacy/Terms Modal Functionality
document.addEventListener('DOMContentLoaded', function(){
  var modalButtons = document.querySelectorAll('[data-modal-target]');
  var modals = document.querySelectorAll('.privacy-modal, .terms-modal, .cancellation-modal');
  var closeButtons = document.querySelectorAll('.privacy-modal__close, .terms-modal__close, .cancellation-modal__close');

  function closeModal(modal) {
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    document.body.classList.remove('modal-open');
  }

  function openModal(modal) {
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-open');
  }

  modalButtons.forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      var modalId = btn.getAttribute('data-modal-target');
      var modal = document.getElementById(modalId);
      openModal(modal);
    });
  });

  closeButtons.forEach(function(button){
    button.addEventListener('click', function(){
      closeModal(button.closest('.privacy-modal, .terms-modal, .cancellation-modal'));
    });
  });

  document.querySelectorAll('.privacy-modal__overlay, .terms-modal__overlay, .cancellation-modal__overlay').forEach(function(overlay){
    overlay.addEventListener('click', function(){
      closeModal(overlay.closest('.privacy-modal, .terms-modal, .cancellation-modal'));
    });
  });

  document.addEventListener('keydown', function(e){
    if (e.key !== 'Escape') return;
    modals.forEach(function(modal){
      if (modal.style.display === 'flex') {
        closeModal(modal);
      }
    });
  });
});
