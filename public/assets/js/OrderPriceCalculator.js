class OrderPriceCalculator {
    constructor(minimumPeople, basePrice) {
        this.minimumPeople = Number(minimumPeople);
        this.basePrice = Number(basePrice);
    }

    calculate(numberOfPeople) {
        const people = Number(numberOfPeople);

        if (
            !Number.isFinite(people) ||
            people < this.minimumPeople ||
            this.minimumPeople <= 0 ||
            this.basePrice < 0
        ) {
            return null;
        }

        // Le serveur est la source de vérité : prix de base par personne × convives.
        const grossPrice = Math.round(this.basePrice * people * 100) / 100;

        return {
            grossPrice,
            discountRate: 0,
            menuPrice: grossPrice
        };
    }
}

window.OrderPriceCalculator = OrderPriceCalculator;
