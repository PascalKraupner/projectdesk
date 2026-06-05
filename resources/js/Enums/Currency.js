export const Currency = {
    EUR: 'EUR',
    USD: 'USD',
};

export const CurrencyOptions = [
    { value: 'EUR', label: 'Euro (€)', symbol: '€' },
    { value: 'USD', label: 'US Dollar ($)', symbol: '$' },
];

export const currencySymbol = (code) => {
    const opt = CurrencyOptions.find((c) => c.value === code);
    return opt ? opt.symbol : code;
};
