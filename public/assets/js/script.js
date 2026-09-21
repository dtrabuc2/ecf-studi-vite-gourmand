(() => {
  const formatPrice = (value) => new Intl.NumberFormat('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(value) + ' €';
  const updateOrderPrice = () => {
    const menuSelect = document.getElementById('menu_id');
    const peopleInput = document.getElementById('number_of_people');
    const cityInput = document.getElementById('delivery_city');
    const distanceInput = document.getElementById('delivery_distance_km');
    const menuPriceElement = document.getElementById('orderMenuPrice');
    const deliveryPriceElement = document.getElementById('orderDeliveryPrice');
    const totalPriceElement = document.getElementById('orderTotalPrice');
    if (!menuSelect || !peopleInput || !menuPriceElement || !deliveryPriceElement || !totalPriceElement || !window.OrderPriceCalculator) return;

    const option = menuSelect.options[menuSelect.selectedIndex];
    const people = Number(peopleInput.value || 0);
    const calculator = new window.OrderPriceCalculator(option?.dataset.minPeople || 0, option?.dataset.basePrice || 0);
    const result = calculator.calculate(peopleInput.value);
    const menuPrice = result ? Number(result.menuPrice) : 0;
    const serviceType = document.querySelector('input[name="service_type"]:checked')?.value || 'delivery';
    let deliveryPrice = 0;

    if (serviceType === 'delivery') {
      const city = (cityInput?.value || '').trim().toLowerCase();
      if (city !== 'bordeaux' && city !== '' ) {
        const distance = Number(distanceInput?.value || 0);
        if (Number.isFinite(distance) && distance >= 0) deliveryPrice = 5 + (0.59 * distance);
      }
    }

    const hasEnoughPeople = result !== null && people >= Number(option?.dataset.minPeople || 1);
    menuPriceElement.textContent = hasEnoughPeople ? formatPrice(menuPrice) : '—';
    deliveryPriceElement.textContent = serviceType === 'delivery' && hasEnoughPeople ? formatPrice(deliveryPrice) : formatPrice(0);
    totalPriceElement.textContent = hasEnoughPeople ? formatPrice(menuPrice + deliveryPrice) : '—';

    const label = document.getElementById('orderDeliveryLabel');
    if (label) label.textContent = serviceType === 'delivery' ? 'Livraison' : serviceType === 'pickup' ? 'Retrait' : 'Service sur place';
  };

  document.addEventListener('DOMContentLoaded', () => {
    ['menu_id','number_of_people','delivery_city','delivery_distance_km'].forEach(id => {
      const field = document.getElementById(id);
      field?.addEventListener('input', updateOrderPrice);
      field?.addEventListener('change', updateOrderPrice);
    });
    document.querySelectorAll('input[name="service_type"]').forEach(input => input.addEventListener('change', updateOrderPrice));
    updateOrderPrice();

    document.querySelectorAll('[data-confirm]').forEach((button) => {
      button.addEventListener('click', (event) => {
        const message = button.dataset.confirm;
        if (message && !window.confirm(message)) event.preventDefault();
      });
    });
  });
})();