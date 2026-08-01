const pad = (n) => String(n).padStart(2, '0');

export const toDateInput = (date) =>
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

/** Clamped: setMonth() alone turns 31 Jan + 1 month into 3 Mar. */
export const inMonths = (months, from = new Date()) => {
    const d = new Date(from);
    const day = d.getDate();
    d.setDate(1);
    d.setMonth(d.getMonth() + months);
    const lastDayOfMonth = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    d.setDate(Math.min(day, lastDayOfMonth));
    return toDateInput(d);
};

/** First and last day of the month `monthOffset` away from today. */
export const monthRange = (monthOffset = 0) => {
    const now = new Date();
    return {
        from: toDateInput(new Date(now.getFullYear(), now.getMonth() + monthOffset, 1)),
        to: toDateInput(new Date(now.getFullYear(), now.getMonth() + monthOffset + 1, 0)),
    };
};
