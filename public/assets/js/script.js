(() => {
  const updateOrderPrice = () => {
    const menuSelect = document.getElementById('menu_id');
    const peopleInput = document.getElementById('number_of_people');
    const cityInput = document.getElementById('delivery_city');
    const distanceInput = document.getElementById('delivery_distance_km');
    const menuPriceElement = document.getElementById('orderMenuPrice');
    const deliveryPriceElement = document.getElementById('orderDeliveryPrice');
    const totalPriceElement = document.getElementById('orderTotalPrice');

    if (!menuSelect || !peopleInput || !menuPriceElement || !deliveryPriceElement || !totalPriceElement || !window.OrderPriceCalculator) {
      return;
    }

    const option = menuSelect.options[menuSelect.selectedIndex];
    const calculator = new window.OrderPriceCalculator(
      option?.dataset.minPeople || 0,
      option?.dataset.basePrice || 0
    );
    const result = calculator.calculate(peopleInput.value);
    const menuPrice = result ? result.menuPrice : 0;

    const city = (cityInput?.value || '').trim().toLowerCase();
    let deliveryPrice = 0;

    if (city !== '' && city !== 'bordeaux') {
      const distance = Number(distanceInput?.value || 0);
      if (Number.isFinite(distance) && distance >= 0) {
        deliveryPrice = 5 + (0.59 * distance);
      }
    }

    const formatPrice = (value) => new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value) + ' €';

    menuPriceElement.textContent = formatPrice(menuPrice);
    deliveryPriceElement.textContent = formatPrice(deliveryPrice);
    totalPriceElement.textContent = formatPrice(menuPrice + deliveryPrice);
  };

  document.addEventListener('DOMContentLoaded', () => {
    const fields = [
      document.getElementById('menu_id'),
      document.getElementById('number_of_people'),
      document.getElementById('delivery_city'),
      document.getElementById('delivery_distance_km')
    ];

    fields.forEach((field) => {
      field?.addEventListener('input', updateOrderPrice);
      field?.addEventListener('change', updateOrderPrice);
    });

    updateOrderPrice();

    document.querySelectorAll('[data-confirm]').forEach((button) => {
      button.addEventListener('click', (event) => {
        const message = button.dataset.confirm;
        if (message && !window.confirm(message)) event.preventDefault();
      });
    });
  });
})();