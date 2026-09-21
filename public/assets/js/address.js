(() => {
  const address = document.getElementById('delivery_address');
  if (!address) return;

  const wrapper = address.parentElement;
  const hint = document.createElement('div');
  hint.className = 'form-text address-hint';
  hint.setAttribute('aria-live', 'polite');
  wrapper?.appendChild(hint);

  address.addEventListener('blur', () => {
    const value = address.value.trim().replace(/\s+/g, ' ');
    if (value === '') return;

    const normalized = value.replace(/\b(rte|route)\b/i, 'route');
    if (normalized !== value) {
      hint.textContent = 'Suggestion d’adresse : ' + normalized;
      return;
    }

    hint.textContent = 'Adresse conservée. Ajoutez résidence, bâtiment, étage et instructions si nécessaire.';
  });
})();