(() => {
  const form = document.getElementById('orderFinalForm');
  if (!form) return;

  const modalElement = document.getElementById('guestModal');
  const openGuestsModal = document.getElementById('openGuestsModal');
  const confirmGuestCount = document.getElementById('confirmGuestCount');
  const numberField = document.getElementById('number_of_people');
  const confirmedField = document.getElementById('confirmedGuestCount');
  const config = document.getElementById('orderConfiguration');
  const summary = document.getElementById('guestCountSummary');
  const menuSelect = document.getElementById('menu_id');
  const menuMessage = document.getElementById('menuAvailabilityMessage');
  const menuLegend = document.getElementById('menuAvailabilityLegend');
  const dateField = document.getElementById('delivery_date');
  const timeField = document.getElementById('delivery_time');
  const dateMessage = document.getElementById('dateAvailabilityMessage');
  const previewMenu = document.getElementById('orderMenuPrice');
  const previewDiscount = document.getElementById('orderDiscount');
  const previewDelivery = document.getElementById('orderDeliveryPrice');
  const previewTotal = document.getElementById('orderPreviewTotal');
  const priceMessage = document.getElementById('orderPriceMessage');
  const deliveryFields = document.getElementById('deliveryFields');

  let menuOptions = [];
  let loadingMenus = false;

  const formatPrice = (value) => Number(value || 0).toLocaleString('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' €';

  const getGuests = () => Number(confirmedField?.value || numberField?.value || 0);
  const getServiceType = () => document.querySelector('input[name="service_type"]:checked')?.value || 'delivery';
  const getDeliveryCity = () => document.getElementById('delivery_city')?.value?.trim() || '';
  const getDeliveryDistance = () => {
    const value = document.getElementById('delivery_distance_km')?.value ?? '';
    return value === '' ? '' : value;
  };

  const setPriceState = ({
    menuPrice = 0,
    discountRate = 0,
    deliveryCost = null,
    totalPrice = 0,
    ready = false,
    message = ''
  } = {}) => {
    if (previewMenu) previewMenu.textContent = formatPrice(menuPrice);
    if (previewDiscount) previewDiscount.textContent = Number(discountRate || 0).toLocaleString('fr-FR') + ' %';
    if (previewDelivery) previewDelivery.textContent = deliveryCost === null ? 'À renseigner' : formatPrice(deliveryCost);
    if (previewTotal) previewTotal.textContent = ready ? formatPrice(totalPrice) : 'À calculer';
    if (priceMessage) {
      priceMessage.textContent = message || (ready
        ? 'Montant calculé par le serveur.'
        : 'Renseignez les informations nécessaires pour obtenir le total.');
    }
  };

  const loadMenuOptions = async (people) => {
    if (!Number.isInteger(people) || people < 1) return;

    loadingMenus = true;
    menuSelect.innerHTML = '<option value="">Chargement des menus…</option>';
    menuSelect.disabled = true;

    try {
      const payload = await window.VgApi.get(
        '/orders/menu-options?number_of_people=' + encodeURIComponent(people)
      );

      const data = payload?.data || {};
      if (data.quote_required) {
        form.submit();
        return;
      }

      menuOptions = Array.isArray(data.menus) ? data.menus : [];
      menuSelect.innerHTML = '<option value="">Sélectionnez un menu</option>';

      let available = 0;

      menuOptions.forEach((menu) => {
        const option = document.createElement('option');
        option.value = String(menu.id);
        option.dataset.available = menu.available ? '1' : '0';
        option.dataset.menuPrice = menu.menu_price === null ? '' : String(menu.menu_price);
        option.disabled = !menu.available;
        option.classList.add(menu.available ? 'text-success' : 'text-danger');

        const suffix = menu.available
          ? ' — DISPONIBLE'
          : ' — INDISPONIBLE : ' + (menu.reason || 'conditions non remplies');

        option.textContent =
          menu.title
          + ' — base ' + formatPrice(menu.base_price)
          + ' — min. ' + menu.min_people + ' pers.'
          + suffix;

        menuSelect.appendChild(option);

        if (menu.available) {
          available++;
        }
      });

      menuSelect.disabled = menuOptions.length === 0;

      if (menuLegend) {
        menuLegend.innerHTML =
          '<span class="text-success fw-semibold">Vert = disponible</span> · ' +
          '<span class="text-danger fw-semibold">Rouge = indisponible</span>';
      }

      if (menuMessage) {
        menuMessage.className = available > 0 ? 'alert alert-success' : 'alert alert-danger';
        menuMessage.textContent = available > 0
          ? available + ' menu(s) disponible(s) pour ' + people + ' convive(s).'
          : 'Aucun menu disponible pour ' + people + ' convive(s).';
      }

      const oldMenuId = Number(form.dataset.oldMenuId || 0);
      if (oldMenuId > 0) {
        const oldOption = Array.from(menuSelect.options).find(
          (option) => Number(option.value) === oldMenuId && !option.disabled
        );

        if (oldOption) {
          menuSelect.value = oldOption.value;
        }
      }

      await updatePrice();
    } catch (error) {
      menuOptions = [];
      menuSelect.innerHTML = '<option value="">Menus indisponibles</option>';
      menuSelect.disabled = true;
      if (menuMessage) {
        menuMessage.className = 'alert alert-danger';
        menuMessage.textContent = error?.message || 'Impossible de charger les disponibilités.';
      }
      setPriceState();
    } finally {
      loadingMenus = false;
    }
  };

  const updatePrice = async () => {
    const menuId = Number(menuSelect?.value || 0);
    const people = getGuests();

    if (!menuId || !people || loadingMenus) {
      setPriceState();
      return;
    }

    const params = new URLSearchParams({
      menu_id: String(menuId),
      number_of_people: String(people),
      service_type: getServiceType(),
      delivery_city: getDeliveryCity(),
      delivery_distance_km: getDeliveryDistance()
    });

    try {
      const payload = await window.VgApi.get('/orders/price-preview?' + params.toString());
      setPriceState(payload?.data || {});
    } catch (error) {
      setPriceState({
        message: error?.message || 'Impossible de calculer le prix.'
      });
    }
  };

  const loadSlots = async () => {
    const date = dateField?.value || '';

    if (!timeField) return;

    timeField.innerHTML = '<option value="">Choisir un créneau</option>';
    timeField.disabled = true;

    if (!date) {
      if (dateMessage) dateMessage.textContent = 'Choisissez une date.';
      return;
    }

    try {
      const payload = await window.VgApi.get(
        '/orders/availability?date=' + encodeURIComponent(date)
      );
      const data = payload?.data || {};
      const slots = Array.isArray(data.slots) ? data.slots : [];

      slots.forEach((slot) => {
        const option = document.createElement('option');
        option.value = slot;
        option.textContent = slot;
        timeField.appendChild(option);
      });

      timeField.disabled = slots.length === 0;

      if (dateMessage) {
        if (data.open && slots.length > 0) {
          const windows = (data.windows || [])
            .map((window) => window.join('–'))
            .join(' / ');

          dateMessage.className = 'form-text text-success';
          dateMessage.textContent = data.day_label + ' ouvert : ' + windows + '.';
        } else {
          dateMessage.className = 'form-text text-danger';
          dateMessage.textContent = data.day_label
            ? data.day_label + ' : fermé ou plus aucun créneau disponible.'
            : 'Date fermée ou indisponible.';
        }
      }
    } catch (error) {
      timeField.innerHTML = '<option value="">Date indisponible</option>';
      if (dateMessage) {
        dateMessage.className = 'form-text text-danger';
        dateMessage.textContent = error?.message || 'Impossible de vérifier la date.';
      }
    }
  };

  const syncServiceType = () => {
    const type = getServiceType();
    const isDelivery = type === 'delivery';

    deliveryFields?.classList.toggle('d-none', !isDelivery);

    deliveryFields?.querySelectorAll('input, textarea').forEach((field) => {
      field.required = isDelivery && [
        'delivery_address',
        'delivery_postal_code',
        'delivery_city'
      ].includes(field.name);
    });

    if (!isDelivery) {
      deliveryFields?.querySelectorAll('input, textarea').forEach((field) => {
        if (field.name !== 'delivery_instructions') {
          field.required = false;
        }
      });
    }

    void updatePrice();
  };

  const confirmGuestsNow = async () => {
    const message = document.getElementById('guestValidationMessage');
    const people = Number(numberField?.value || 0);

    if (!Number.isInteger(people) || people < 1) {
      if (message) {
        message.textContent = 'Saisissez un nombre entier de convives supérieur à 0.';
        message.classList.remove('d-none');
      }
      return;
    }

    if (people >= 50) {
      form.submit();
      return;
    }

    await loadMenuOptions(people);

    if (menuOptions.length === 0) {
      if (message) {
        message.textContent = 'Aucun menu n’est disponible pour ce nombre de convives.';
        message.classList.remove('d-none');
      }
      return;
    }

    confirmedField.value = String(people);
    summary.textContent = people + ' convive' + (people > 1 ? 's' : '');
    config.classList.remove('d-none');
    message?.classList.add('d-none');
    window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
  };

  const preventInvalidSubmit = (event) => {
    if (loadingMenus) {
      event.preventDefault();
      return;
    }

    const people = Number(confirmedField?.value || 0);
    const selected = menuOptions.find(
      (menu) => Number(menu.id) === Number(menuSelect?.value)
    );

    if (people < 1 || !selected || !selected.available) {
      event.preventDefault();
      window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
      return;
    }

    if (!dateField?.value || !timeField?.value) {
      event.preventDefault();
      return;
    }

    const validTime = Array.from(timeField.options).some(
      (option) => option.value === timeField.value
    );

    if (!validTime) {
      event.preventDefault();
    }
  };

  openGuestsModal?.addEventListener(
    'click',
    () => window.bootstrap.Modal.getOrCreateInstance(modalElement).show()
  );

  confirmGuestCount?.addEventListener('click', () => void confirmGuestsNow());
  menuSelect?.addEventListener('change', () => void updatePrice());
  dateField?.addEventListener('change', () => void loadSlots());

  document.querySelectorAll('input[name="service_type"]').forEach((input) => {
    input.addEventListener('change', syncServiceType);
  });

  ['delivery_city', 'delivery_distance_km'].forEach((id) => {
    document.getElementById(id)?.addEventListener('input', () => void updatePrice());
  });

  form.addEventListener('submit', preventInvalidSubmit);

  if (form.dataset.oldMenuId === undefined) {
    form.dataset.oldMenuId = '';
  }

  const initialGuests = Number(confirmedField?.value || 0);

  if (initialGuests > 0) {
    summary.textContent = initialGuests + ' convive' + (initialGuests > 1 ? 's' : '');
    config.classList.remove('d-none');
    void loadMenuOptions(initialGuests);
  } else {
    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
  }

  syncServiceType();
  void loadSlots();
})();
