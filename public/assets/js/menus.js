(() => {
  const form = document.getElementById('menuFilters');
  const grid = document.getElementById('menuGrid');
  const status = document.getElementById('menuFilterStatus');

  if (!form || !grid) return;

  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  const imageUrl = (image) => String(image?.url || image?.path || '').trim();

  const formatPrice = (value) => Number(value || 0).toLocaleString('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' €';

  const regimeLabel = (regime) => ({
    classic: 'Classique',
    vegetarian: 'Végétarien',
    vegan: 'Végétalien',
    other: 'Autre'
  }[regime] || regime || 'Non précisé');

  const renderGallery = (menu) => {
    const images = Array.isArray(menu.images) ? menu.images : [];
    const primary = imageUrl(images[0]);

    if (!primary) {
      return '<div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height:250px;">Aucune image disponible</div>';
    }

    const thumbs = images.slice(1).map((image, index) => {
      const url = imageUrl(image);
      if (!url) return '';
      return '<img src="' + escapeHtml(url) + '" class="rounded" alt="' +
        escapeHtml(image.alt_text || menu.title) +
        '" style="width:72px;height:52px;object-fit:cover;" loading="lazy">';
    }).join('');

    return '<div class="position-relative">' +
      '<img src="' + escapeHtml(primary) + '" class="card-img-top" alt="' +
      escapeHtml(images[0]?.alt_text || menu.title) +
      '" style="height:250px;object-fit:cover;">' +
      (thumbs ? '<div class="position-absolute bottom-0 start-0 end-0 d-flex gap-2 p-2 bg-dark bg-opacity-50">' + thumbs + '</div>' : '') +
      '</div>';
  };

  const renderDishes = (menu) => {
    const dishes = { starter: [], main: [], dessert: [] };

    (Array.isArray(menu.dishes) ? menu.dishes : []).forEach((dish) => {
      if (dishes[dish.category]) dishes[dish.category].push(dish.name);
    });

    return '<div class="bg-light p-3 rounded mb-3 small text-muted">' +
      '<p class="mb-1"><strong>Entrées :</strong> ' +
      escapeHtml(dishes.starter.slice(0, 3).join(', ') || 'Non renseignées') +
      '</p>' +
      '<p class="mb-1"><strong>Plats :</strong> ' +
      escapeHtml(dishes.main.slice(0, 3).join(', ') || 'Non renseignés') +
      '</p>' +
      '<p class="mb-0"><strong>Desserts :</strong> ' +
      escapeHtml(dishes.dessert.slice(0, 3).join(', ') || 'Non renseignés') +
      '</p>' +
      '</div>';
  };

  const renderMenus = (menus) => {
    if (!Array.isArray(menus) || menus.length === 0) {
      grid.innerHTML = '<div class="col-12"><div class="alert alert-warning">Aucun menu ne correspond aux critères.</div></div>';
      return;
    }

    grid.innerHTML = menus.map((menu) => {
      return '<div class="col-md-6 col-lg-4">' +
        '<article class="card h-100 border-0 shadow-sm overflow-hidden">' +
        renderGallery(menu) +
        '<div class="card-body d-flex flex-column">' +
        '<div class="d-flex flex-wrap gap-2 align-items-center">' +
        '<span class="badge bg-primary-subtle text-primary">' + escapeHtml(menu.theme) + '</span>' +
        '<span class="badge bg-success-subtle text-success">' + escapeHtml(regimeLabel(menu.dietary_regime)) + '</span>' +
        '</div>' +
        '<h2 class="h4 text-primary mt-3">' + escapeHtml(menu.title) + '</h2>' +
        '<p class="text-muted flex-grow-1">' + escapeHtml(menu.description) + '</p>' +
        renderDishes(menu) +
        '<p class="small mb-2"><strong>Minimum :</strong> ' +
        Number(menu.min_people || 0) + ' personnes</p>' +
        '<p class="small mb-1"><strong>Prix de base :</strong> ' +
        formatPrice(menu.base_price) + '</p>' +
        '<p class="small mb-3"><strong>Stock disponible :</strong> ' +
        Number(menu.available_stock || 0) + ' commande(s)</p>' +
        '<p class="small text-muted mb-3"><strong>Conditions :</strong> ' +
        escapeHtml(menu.conditions || 'Voir le détail du menu.') + '</p>' +
        '<div class="d-grid gap-2 mt-auto">' +
        '<a class="btn btn-outline-primary" href="/menus/' + Number(menu.id) + '">Voir le détail</a>' +
        '<a class="btn btn-primary" href="/orders/new?menu=' + Number(menu.id) + '">Commander</a>' +
        '</div></div></article></div>';
    }).join('');
  };

  const load = async (url) => {
    status?.classList.remove('visually-hidden');

    if (status) status.textContent = 'Chargement des menus…';

    grid.setAttribute('aria-busy', 'true');

    try {
      const menus = await window.VgApi.get(url);
      renderMenus(menus);

      if (status) {
        status.textContent =
          (Array.isArray(menus) ? menus.length : 0) +
          ' menu(s) affiché(s).';
      }
    } catch (error) {
      grid.innerHTML =
        '<div class="col-12"><div class="alert alert-danger">' +
        escapeHtml(error?.message || 'Impossible de charger les menus.') +
        '</div></div>';

      if (status) {
        status.textContent = 'Erreur lors du chargement des menus.';
      }
    } finally {
      grid.removeAttribute('aria-busy');
    }
  };

  const buildUrl = () => {
    const endpoint = form.dataset.filterEndpoint || '/menus/filter';
    const params = new URLSearchParams(new FormData(form));

    [...params.entries()].forEach(([key, value]) => {
      if (String(value).trim() === '') params.delete(key);
    });

    const query = params.toString();
    return query ? endpoint + '?' + query : '/public/menus';
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    load(buildUrl());
  });

  form.addEventListener('reset', () => {
    window.setTimeout(() => load('/public/menus'), 0);
  });

  load('/public/menus');
})();
