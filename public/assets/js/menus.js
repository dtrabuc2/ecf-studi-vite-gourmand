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

  const imageUrl = (path) => {
    const value = String(path ?? '').trim();
    if (!value) return '';
    if (/^https?:\/\//i.test(value) || value.startsWith('/')) return value;
    return '/uploads/' + value.replace(/^uploads\//i, '').replace(/^\/+/, '');
  };

  const renderMenus = (menus) => {
    if (!Array.isArray(menus) || menus.length === 0) {
      grid.innerHTML = '<div class="col-12"><div class="alert alert-warning">Aucun menu ne correspond aux critères.</div></div>';
      return;
    }

    grid.innerHTML = menus.map((menu) => {
      const id = Number(menu.id);
      const theme = escapeHtml(menu.theme);
      const title = escapeHtml(menu.title);
      const description = escapeHtml(menu.description);
      const image = imageUrl(menu.images?.[0]?.path);
      const price = Number(menu.base_price || 0).toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });

      const dishes = { starter: [], main: [], dessert: [] };
      (Array.isArray(menu.dishes) ? menu.dishes : []).forEach((dish) => {
        if (dishes[dish.category]) dishes[dish.category].push(dish.name);
      });

      const imageBlock = image
        ? '<img src="' + escapeHtml(image) + '" class="card-img-top" alt="' + title + '" style="height:220px;object-fit:cover;">'
        : '<div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height:220px;">Aucune image disponible</div>';

      return '<div class="col-md-6 col-lg-4" data-menu-card>' +
        '<article class="card h-100 border-0 shadow-sm overflow-hidden">' +
          imageBlock +
          '<div class="card-body d-flex flex-column">' +
            '<div class="d-flex justify-content-between gap-2">' +
              '<span class="badge bg-primary-subtle text-primary">' + theme + '</span>' +
              '<strong>' + price + ' €</strong>' +
            '</div>' +
            '<h2 class="h4 text-primary mt-3">' + title + '</h2>' +
            '<p class="text-muted flex-grow-1">' + description + '</p>' +
            '<div class="bg-light p-3 rounded mb-3 small text-muted">' +
              '<p class="mb-1"><strong>Entrées :</strong> ' + escapeHtml(dishes.starter.slice(0, 2).join(', ') || 'Non renseignées') + '</p>' +
              '<p class="mb-1"><strong>Plats :</strong> ' + escapeHtml(dishes.main.slice(0, 2).join(', ') || 'Non renseignés') + '</p>' +
              '<p class="mb-0"><strong>Desserts :</strong> ' + escapeHtml(dishes.dessert.slice(0, 2).join(', ') || 'Non renseignés') + '</p>' +
            '</div>' +
            '<p class="small mb-3">Minimum : ' + Number(menu.min_people || 0) + ' personnes · Stock : ' + Number(menu.available_stock || 0) + '</p>' +
            '<div class="d-grid gap-2 mt-auto">' +
              '<a class="btn btn-outline-primary" href="/menus/' + id + '">Voir le détail</a>' +
              '<a class="btn btn-primary" href="/orders/new?menu=' + id + '">Commander</a>' +
            '</div>' +
          '</div>' +
        '</article>' +
      '</div>';
    }).join('');
  };

  const load = async (url) => {
    status?.classList.remove('visually-hidden');
    if (status) status.textContent = 'Chargement des menus…';
    grid.setAttribute('aria-busy', 'true');

    try {
      const menus = await window.VgApi.get(url);
      renderMenus(menus);
      if (status) status.textContent = menus.length + ' menu(s) affiché(s).';
    } catch (error) {
      grid.innerHTML = '<div class="col-12"><div class="alert alert-danger">' +
        escapeHtml(error?.message || 'Impossible de charger les menus.') +
      '</div></div>';
      if (status) status.textContent = 'Erreur lors du chargement des menus.';
    } finally {
      grid.removeAttribute('aria-busy');
    }
  };

  const buildUrl = () => {
    const endpoint = form.dataset.filterEndpoint || '/menus/filter';
    const params = new URLSearchParams(new FormData(form));

    for (const [key, value] of [...params.entries()]) {
      if (String(value).trim() === '') params.delete(key);
    }

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
})();