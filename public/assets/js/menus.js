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
      const price = Number(menu.base_price || 0).toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });

      return `
        <div class="col-md-6 col-lg-4" data-menu-card>
          <article class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between gap-2">
                <span class="badge bg-primary-subtle text-primary">${theme}</span>
                <strong>${price} €</strong>
              </div>
              <h2 class="h4 text-primary mt-3">${title}</h2>
              <p class="text-muted flex-grow-1">${description}</p>
              <p class="small">
                Minimum : ${Number(menu.min_people || 0)} personnes
                · Stock : ${Number(menu.available_stock || 0)}
              </p>
              <a class="btn btn-outline-primary" href="/menus/${id}">Voir le détail</a>
            </div>
          </article>
        </div>
      `;
    }).join('');
  };

  const load = async (url) => {
    try {
      status?.classList.remove('visually-hidden');
      if (status) status.textContent = 'Chargement des menus…';
      grid.setAttribute('aria-busy', 'true');

      const menus = await window.VgApi.get(url);
      renderMenus(menus);

      if (status) status.textContent = menus.length + ' menu(s) affiché(s).';
    } catch (error) {
      grid.innerHTML = `
        <div class="col-12">
          <div class="alert alert-danger">${escapeHtml(error.message)}</div>
        </div>`;
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