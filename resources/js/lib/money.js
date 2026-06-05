export const formatMoney = (amount, currency = 'EUR') => {
    const value = Number(amount ?? 0);
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(value);
};

export const amountFromSeconds = (seconds, hourlyRate) => {
    if (hourlyRate == null) return null;
    const hours = (Number(seconds) || 0) / 3600;
    return hours * Number(hourlyRate);
};
