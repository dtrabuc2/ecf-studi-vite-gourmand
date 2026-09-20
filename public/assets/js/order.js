(() => {
  const builder = document.getElementById('orderBuilder');
  if (!builder) return;

  const menuSelect = document.getElementById('selectMenu');
  const detail = document.getElementById('detailMenuSelectionne');
  const image = document.getElementById('imageMenu');
  const title = document.getElementById('nomMenuAffiche');
  const description = document.getElementById('descriptionMenu');
  const price = document.getElementById('prixMenuAffiche');
  const customization = document.getElementById('sectionPersonnalisation');
  const serviceField = document.getElementById('selected_service_type');
  const finalForm = document.getElementById('orderFinalForm');
  const finalMenuSelect = document.getElementById('menu_id');

  let menus = [];
  let serviceType = 'menu';
  let selected = null;

  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  const formatPrice = (value) => Number(value || 0).toLocaleString('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' €';

  const renderChoiceList = (id, name, items) => {
    const target = document.getElementById(id);
    if (!target) return;

    if (!items.length) {
      target.innerHTML = '<p class="text-muted small mb-0">Aucune option disponible.</p>';
      return;
    }

    target.innerHTML = items.map((item, index) => {
      const safe = escapeHtml(item);
      return '<div class="form-check mb-2">' +
        '<input class="form-check-input" type="radio" name="' + name + '" id="' + id + '-' + index + '" value="' + safe + '"' + (index === 0 ? ' checked' : '') + '>' +
        '<label class="form-check-label small" for="' + id + '-' + index + '">' + safe + '</label>' +
      '</div>';
    }).join('');
  };

  const renderBoissons = () => {
    const type = document.querySelector('input[name="typeBoisson"]:checked')?.value || 'sans';
    const items = type === 'avec'
      ? ['Vin rouge', 'Vin blanc', 'Champagne', 'Bière artisanale']
      : ['Eaux aromatisées', 'Jus de pomme artisanal', 'Jus d’orange pressé', 'Citronnade maison', 'Thé glacé pêche'];
    renderChoiceList('listBoissons', 'boisson', items);
  };

  const renderDigestifs = () => {
    const target = document.getElementById('selectDigestif');
    if (!target) return;
    target.innerHTML = '<option value="">Aucun</option>' +
      ['Cognac VSOP', 'Armagnac', 'Limoncello artisanal', 'Calvados', 'Get 27']
        .map((item) => '<option value="' + escapeHtml(item) + '">' + escapeHtml(item) + '</option>')
        .join('');
  };

  const renderSelected = () => {
    if (!selected) return;

    const src = String(selected.images?.[0]?.url || selected.images?.[0]?.path || '').trim();

    detail?.classList.remove('d-none');
    customization?.classList.remove('d-none');

    if (image) {
      image.src = src;
      image.alt = selected.title || 'Image du menu';
      image.classList.toggle('d-none', !src);
    }
    if (title) title.textContent = selected.title || '';
    if (description) description.textContent = selected.description || '';
    if (price) price.textContent = formatPrice(selected.base_price);

    const dishes = { starter: [], main: [], dessert: [] };
    (selected.dishes || []).forEach((dish) => {
      if (dishes[dish.category]) dishes[dish.category].push(dish.name);
    });

    if (serviceType === 'menu') {
      renderChoiceList('listEntrees', 'entree', dishes.starter);
      renderChoiceList('listPlats', 'plat', dishes.main);
      renderChoiceList('listDesserts', 'dessert', dishes.dessert);
    } else {
      renderChoiceList('listEntrees', 'entree', []);
      renderChoiceList('listPlats', 'plat', [selected.title]);
      const desserts = [];
      menus.forEach((menu) => (menu.dishes || []).forEach((dish) => {
        if (dish.category === 'dessert' && !desserts.includes(dish.name)) desserts.push(dish.name);
      }));
      renderChoiceList('listDesserts', 'dessert', desserts);
    }

    renderBoissons();
    renderDigestifs();

    if (finalMenuSelect && selected.service_type !== 'plat') {
      finalMenuSelect.value = String(selected.id);
    }
  };

  const populate = () => {
    if (!menuSelect) return;
    menuSelect.innerHTML = '<option value="">-- Sélectionnez une formule --</option>' +
      menus.map((item) =>
        '<option value="' + Number(item.id) + '">' +
        escapeHtml(item.title) + ' — ' + formatPrice(item.base_price) +
        '</option>'
      ).join('');
  };

  const loadMenus = async () => {
    const data = await window.VgApi.get('/public/menus');
    menus = Array.isArray(data) ? data : [];

    document.querySelectorAll('.service-selector').forEach((button) => {
      button.addEventListener('click', () => {
        serviceType = button.dataset.type || 'menu';
        if (serviceField) serviceField.value = serviceType;
        builder.classList.remove('d-none');
        populate();
      });
    });

    menuSelect?.addEventListener('change', () => {
      const id = Number(menuSelect.value);
      selected = menus.find((item) => Number(item.id) === id) || null;
      renderSelected();
    });

    document.querySelectorAll('input[name="typeBoisson"]').forEach((input) => {
      input.addEventListener('change', renderBoissons);
    });

    const params = new URLSearchParams(window.location.search);
    const presetId = Number(params.get('menu') || 0);
    if (params.get('service') === 'plat') serviceType = 'plat';
    if (serviceField) serviceField.value = serviceType;

    if (presetId > 0) {
      builder.classList.remove('d-none');
      populate();
      menuSelect.value = String(presetId);
      selected = menus.find((item) => Number(item.id) === presetId) || null;
      renderSelected();
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    loadMenus().catch((error) => {
      builder.insertAdjacentHTML('afterbegin',
        '<div class="alert alert-danger">' + escapeHtml(error?.message || 'Impossible de charger les menus.') + '</div>');
    });
  });
})();