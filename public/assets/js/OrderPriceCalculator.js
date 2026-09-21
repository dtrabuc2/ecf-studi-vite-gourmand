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

        // Le serveur reste la source de vérité.
        // Règle ECF : remise de 10 % à partir de 5 personnes au-dessus du minimum.
        const grossPrice = Math.round(this.basePrice * people * 100) / 100;
        const hasDiscount = people >= this.minimumPeople + 5;
        const discountRate = hasDiscount ? 10 : 0;
        const menuPrice = Math.round(grossPrice * (1 - (discountRate / 100)) * 100) / 100;

        return {
            grossPrice,
            discountRate,
            menuPrice
        };
    }
}

window.OrderPriceCalculator = OrderPriceCalculator;
