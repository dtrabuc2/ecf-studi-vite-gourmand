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
  const recapOptions = document.getElementById('selected_options');
  const continueButton = document.getElementById('btnVersRecap');
  const digestif = document.getElementById('selectDigestif');
  const deliveryFields = document.getElementById('deliveryFields');
  const ingredients = document.getElementById('listIngredients');

  let menus = [];
  let serviceType = 'menu';
  let selected = null;
  let selectedDish = null;

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
      const itemName = typeof item === 'string' ? item : (item.name || '');
      const itemDescription = typeof item === 'string' ? '' : (item.description || '');
      const safe = escapeHtml(itemName);
      const description = itemDescription
        ? '<span class="d-block text-muted small">' + escapeHtml(itemDescription) + '</span>'
        : '';
      return '<div class="form-check mb-2">' +
        '<input class="form-check-input" type="radio" name="' + name + '" id="' + id + '-' + index + '" value="' + safe + '"' + (index === 0 ? ' checked' : '') + '>' +
      '<label class="form-check-label small" for="' + id + '-' + index + '">' + safe + description + '</label>' +
      '</div>';
    }).join('');
  };

  const renderBoissons = () => {
    const type = document.querySelector('input[name="typeBoisson"]:checked')?.value || 'sans';
    const items = type === 'avec'
      ? ['Vin rouge', 'Vin blanc', 'Champagne', 'Bière artisanale']
      : ['Eaux aromatisées', 'Jus de pomme artisanal', 'Jus d’orange pressé', 'Citronnade maison', 'Thé glacé pêche'];
    renderChoiceList('listBoissons', 'boisson', items);
    if (digestif) {
      digestif.disabled = type !== 'avec';
      if (type !== 'avec') digestif.value = '';
    }
  };

  const contactPhone = document.getElementById('contact_phone');
  const serviceTimeLabel = document.getElementById('serviceTimeLabel');
  const serviceTimeHelp = document.getElementById('serviceTimeHelp');
  const deliveryInstructionsField = document.getElementById('deliveryInstructionsField');
  const pickupLocationNotice = document.getElementById('pickupLocationNotice');
  const onSiteAddressNotice = document.getElementById('onSiteAddressNotice');

  const syncServiceType = () => {
    const selectedType = document.querySelector('input[name="service_type"]:checked')?.value || 'delivery';
    const isDelivery = selectedType === 'delivery';
    const isPickup = selectedType === 'pickup';
    const isOnSite = selectedType === 'on_site';

    deliveryFields?.classList.toggle('d-none', !isDelivery);
    pickupLocationNotice?.classList.toggle('d-none', isDelivery);
    onSiteAddressNotice?.classList.toggle('d-none', !isOnSite);

    deliveryFields?.querySelectorAll('input, textarea').forEach((field) => {
      field.required = isDelivery && ['delivery_address', 'delivery_postal_code', 'delivery_city'].includes(field.name);
    });

    if (deliveryInstructionsField) {
      deliveryInstructionsField.classList.toggle('d-none', !isDelivery);
    }

    if (serviceTimeLabel) {
      serviceTimeLabel.textContent = isDelivery
        ? 'Heure de livraison'
        : isPickup
          ? 'Heure de retrait'
          : 'Heure d’arrivée';
    }

    if (serviceTimeHelp) {
      serviceTimeHelp.textContent = isDelivery
        ? 'Heure prévue de remise au client.'
        : isPickup
          ? 'Heure à laquelle la commande sera retirée.'
          : 'Heure prévue d’arrivée au restaurant.';
    }

    if (contactPhone) {
      contactPhone.required = true;
    }

    if (isDelivery) {
      deliveryFields?.querySelector('#delivery_address')?.setAttribute('placeholder', 'Commencez à saisir une adresse...');
    }

    if (isPickup || isOnSite) {
      deliveryFields?.querySelectorAll('input, textarea').forEach((field) => {
        if (field.name !== 'delivery_instructions') field.required = false;
      });
    }
  };

  document.querySelectorAll('input[name="service_type"]').forEach((input) => {
    input.addEventListener('change', syncServiceType);
  });
  syncServiceType();

  const renderDigestifs = () => {
    const target = document.getElementById('selectDigestif');
    if (!target) return;
    target.innerHTML = '<option value="">Aucun</option>' +
      ['Cognac VSOP', 'Armagnac', 'Limoncello artisanal', 'Calvados', 'Get 27']
        .map((item) => '<option value="' + escapeHtml(item) + '">' + escapeHtml(item) + '</option>')
        .join('');
  };

  const ingredientDictionary = [
    ['foie gras', 'Foie gras'], ['canard', 'Canard'], ['figue', 'Figue'],
    ['saint-jacques', 'Saint-Jacques'], ['noix', 'Noix'], ['agrumes', 'Agrumes'],
    ['herbes', 'Herbes'], ['bœuf', 'Bœuf'], ['boeuf', 'Bœuf'], ['truffe', 'Truffe'],
    ['homard', 'Homard'], ['chocolat', 'Chocolat'], ['praliné', 'Praliné'], ['praline', 'Praliné'],
    ['pistache', 'Pistache'], ['vanille', 'Vanille'], ['tomate', 'Tomate'], ['tomates', 'Tomates'],
    ['avocat', 'Avocat'], ['mangue', 'Mangue'], ['citron', 'Citron'], ['citron vert', 'Citron vert'],
    ['cèpes', 'Cèpes'], ['cepes', 'Cèpes'], ['parmesan', 'Parmesan'], ['courgette', 'Courgette'],
    ['poivron', 'Poivron'], ['meringue', 'Meringue'], ['fruits rouges', 'Fruits rouges'],
    ['saumon', 'Saumon'], ['aneth', 'Aneth'], ['poulet', 'Poulet'], ['soja', 'Soja'],
    ['sauce soja', 'Sauce soja'], ['gambas', 'Gambas'], ['crevette', 'Crevette'],
    ['macaron', 'Macaron'], ['jambon', 'Jambon'], ['fromage', 'Fromage'],
    ['potimarron', 'Potimarron'], ['semoule', 'Semoule'], ['pois chiches', 'Pois chiches'],
    ['falafel', 'Falafels'], ['tahini', 'Tahini'], ['tofu', 'Tofu'], ['gingembre', 'Gingembre'],
    ['ratatouille', 'Ratatouille'], ['haricots verts', 'Haricots verts'], ['olive', 'Olives'],
    ['pommes de terre', 'Pommes de terre'], ['cannelle', 'Cannelle'], ['moules', 'Moules'],
    ['fruits de mer', 'Fruits de mer'], ['riz', 'Riz'], ['quinoa', 'Quinoa'],
    ['champignons', 'Champignons'], ['lait de coco', 'Lait de coco'], ['pâtes', 'Pâtes'],
    ['pates', 'Pâtes'], ['bolognaise', 'Bolognaise'], ['poisson', 'Poisson'],
    ['purée', 'Purée'], ['puree', 'Purée'], ['œuf', 'Œuf'], ['oeuf', 'Œuf'],
    ['lait', 'Lait'], ['crème', 'Crème'], ['creme', 'Crème'], ['noisette', 'Noisette'],
    ['café', 'Café'], ['cafe', 'Café'], ['alcool', 'Alcool']
  ];

  const extractIngredientsFromText = (text) => {
    const normalized = String(text || '').toLocaleLowerCase('fr-FR');
    return ingredientDictionary
      .filter(([token]) => normalized.includes(token))
      .map(([, label]) => label);
  };

  const renderIngredients = () => {
    if (!ingredients) return;

    const sourceDishes = serviceType === 'plat'
      ? (selectedDish ? [selectedDish] : [])
      : (selected?.dishes || []);

    const extracted = sourceDishes.flatMap((dish) => [
      dish.name || '',
      dish.description || ''
    ]).flatMap(extractIngredientsFromText);

    const combined = [...(selected?.allergens || []), ...extracted];
    const values = [...new Map(combined.map((item) => [String(item).toLocaleLowerCase('fr-FR'), item])).values()];

    ingredients.innerHTML = values.length
      ? values.map((item, index) =>
          '<div class="col-12 col-sm-6 col-lg-4">' +
          '<label class="form-check border rounded p-2 h-100">' +
          '<input class="form-check-input me-2" type="checkbox" name="excluded_ingredients[]" value="' + escapeHtml(item) + '">' +
          '<span>' + escapeHtml(item) + '</span>' +
          '</label>' +
          '</div>'
        ).join('')
      : '<p class="small text-muted mb-0">Aucun ingrédient identifiable dans les plats sélectionnés.</p>';
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
      if (dishes[dish.category]) dishes[dish.category].push(dish);
    });

    if (serviceType === 'menu') {
      renderChoiceList('listEntrees', 'entree', dishes.starter);
      renderChoiceList('listPlats', 'plat', dishes.main);
      renderChoiceList('listDesserts', 'dessert', dishes.dessert);
    } else {
      renderChoiceList('listEntrees', 'entree', []);
      const selectedDishForDisplay = selectedDish || dishes.main[0] || dishes.starter[0] || dishes.dessert[0] || null;
      renderChoiceList(
        'listPlats',
        'plat',
        selectedDishForDisplay ? [selectedDishForDisplay] : []
      );
      renderChoiceList('listDesserts', 'dessert', []);
    }

    renderBoissons();
    renderDigestifs();
    renderIngredients();

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
    menus = Array.isArray(data) ? data : (Array.isArray(data?.menus) ? data.menus : []);

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
    const presetDishId = Number(params.get('dish') || 0);
    if (serviceField) serviceField.value = serviceType;

    if (presetId > 0) {
      builder.classList.remove('d-none');
      populate();
      menuSelect.value = String(presetId);
      selected = menus.find((item) => Number(item.id) === presetId) || null;

      if (selected && serviceType === 'plat' && presetDishId > 0) {
        selectedDish = (selected.dishes || []).find(
          (dish) => Number(dish.id) === presetDishId
        ) || null;
      }

      renderSelected();
    }
  };

  const buildOptionsSummary = () => {
    const selectedOptions = Array.from(document.querySelectorAll('#sectionPersonnalisation input:checked'))
      .filter((input) => input.name !== 'excluded_ingredients[]')
      .map((input) => input.value)
      .filter(Boolean);

    document.querySelectorAll('input[name="excluded_ingredients[]"]:checked')
      .forEach((input) => selectedOptions.push('Sans ' + input.value));

    const digestif = document.getElementById('selectDigestif')?.value || '';
    if (digestif && !document.getElementById('selectDigestif')?.disabled) {
      selectedOptions.push('Digestif : ' + digestif);
    }

    if (serviceType === 'plat' && selectedDish?.name) {
      selectedOptions.unshift('À la carte : ' + selectedDish.name);
    }

    return selectedOptions.join(' | ');
  };

  const syncServiceFields = () => {
    const selectedService = document.querySelector('input[name="service_type"]:checked')?.value || 'delivery';
    const delivery = selectedService === 'delivery';
    deliveryFields?.classList.toggle('d-none', !delivery);
    deliveryFields?.querySelectorAll('input, textarea').forEach((field) => {
      field.required = delivery && field.id !== 'delivery_distance_km';
    });
  };

  document.querySelectorAll('input[name="service_type"]').forEach((input) => input.addEventListener('change', syncServiceFields));
  syncServiceFields();

  continueButton?.addEventListener('click', () => {
    if (!selected) {
      window.alert('Sélectionnez une formule avant de continuer.');
      return;
    }

    if (finalMenuSelect) finalMenuSelect.value = String(selected.id);
    if (recapOptions) recapOptions.value = buildOptionsSummary();
    finalForm?.classList.remove('d-none');
    finalForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  document.addEventListener('DOMContentLoaded', () => {
    loadMenus().catch((error) => {
      builder.insertAdjacentHTML('afterbegin',
        '<div class="alert alert-danger">' + escapeHtml(error?.message || 'Impossible de charger les menus.') + '</div>');
    });
  });
})();