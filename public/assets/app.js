// ts6admin — app.js

// ── Globales Bestätigungs-Modal ───────────────────────────────
(function() {
  var modal   = null;
  var okBtn   = null;
  var cancelBtn = null;
  var textEl  = null;

  function initModal() {
    modal     = document.getElementById('confirm-modal');
    okBtn     = document.getElementById('confirm-modal-ok');
    cancelBtn = document.getElementById('confirm-modal-cancel');
    textEl    = document.getElementById('confirm-modal-text');
    if (!modal) return;

    cancelBtn.addEventListener('click', function() {
      modal.style.display = 'none';
    });
    modal.addEventListener('click', function(e) {
      if (e.target === modal) modal.style.display = 'none';
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.style.display !== 'none') {
        modal.style.display = 'none';
      }
    });
  }

  function showConfirm(message, onOk, isDanger) {
    if (!modal) initModal();
    if (!modal) { if (onOk) onOk(); return; }

    textEl.textContent = message;
    okBtn.className    = 'btn ' + (isDanger !== false ? 'btn-danger' : 'btn-primary');
    modal.style.display = 'flex';

    // Alten Listener entfernen
    var newOk = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOk, okBtn);
    okBtn = newOk;

    okBtn.addEventListener('click', function() {
      modal.style.display = 'none';
      if (onOk) onOk();
    });
  }

  // ── data-confirm auf Buttons/Links ────────────────────────
  document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;

    e.preventDefault();
    e.stopPropagation();

    var msg    = el.dataset.confirm || 'Bist du sicher?';
    var isDanger = !el.classList.contains('btn-primary') && !el.classList.contains('btn-success');

    showConfirm(msg, function() {
      if (el.tagName === 'A') {
        window.location.href = el.href;
      } else if (el.form) {
        // data-confirm auf Submit-Button: Formular abschicken
        el.removeAttribute('data-confirm');
        el.form.submit();
      } else {
        el.removeAttribute('data-confirm');
        el.click();
      }
    }, isDanger);
  }, true);

  // ── onclick="return confirm(...)" auf Links abfangen ──────
  document.addEventListener('click', function(e) {
    var el = e.target.closest('a[onclick]');
    if (!el) return;
    var onclickStr = el.getAttribute('onclick') || '';
    var match = onclickStr.match(/confirm\(['"](.+?)['"]\)/);
    if (!match) return;

    e.preventDefault();
    e.stopPropagation();

    showConfirm(match[1], function() {
      el.removeAttribute('onclick');
      el.click();
    });
  }, true);

  document.addEventListener('DOMContentLoaded', initModal);
})();

// ── Toggles ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.toggle[data-target]').forEach(function(el) {
    el.addEventListener('click', function() {
      el.classList.toggle('on');
      var input = document.getElementById(el.dataset.target);
      if (input) input.value = el.classList.contains('on') ? '1' : '0';
    });
  });

  // ── Andere Modals schließen (Escape) ──────────────────────
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay').forEach(function(m) {
        if (m.id !== 'confirm-modal') m.style.display = 'none';
      });
    }
  });

  // ── Success-Alerts automatisch ausblenden ─────────────────
  document.querySelectorAll('.alert-success').forEach(function(el) {
    setTimeout(function() { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s'; }, 3000);
    setTimeout(function() { el.remove(); }, 3500);
  });
});