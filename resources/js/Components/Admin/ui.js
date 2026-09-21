/**
 * GAPURA admin design primitives.
 * Keep layout, color, focus, and state treatment in one place so pages only
 * describe their content.
 */

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2';

const buttonBase =
    `inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold transition-colors disabled:pointer-events-none disabled:opacity-45 ${focusRing}`;

export const btnPrimary =
    `${buttonBase} border border-primary bg-primary text-white shadow-[0_1px_0_rgba(0,0,0,0.08)] hover:border-primary-pressed hover:bg-primary-pressed`;

export const btnPrimarySm =
    `inline-flex min-h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-primary bg-primary px-3.5 text-[13px] font-semibold text-white shadow-[0_1px_0_rgba(0,0,0,0.08)] transition-colors hover:border-primary-pressed hover:bg-primary-pressed disabled:pointer-events-none disabled:opacity-45 ${focusRing}`;

export const btnSecondary =
    `${buttonBase} border border-hairline-strong bg-canvas text-ink hover:border-primary/40 hover:bg-primary-mist/55 hover:text-primary-pressed`;

export const btnSecondarySm =
    `inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-hairline-strong bg-canvas px-3 text-[13px] font-semibold text-charcoal transition-colors hover:border-primary/40 hover:bg-primary-mist/55 hover:text-primary-pressed ${focusRing}`;

export const btnGhost =
    `inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-transparent px-3 text-sm font-semibold text-charcoal transition-colors hover:bg-primary-mist/55 hover:text-primary-pressed ${focusRing}`;

export const btnEdit =
    `inline-flex min-h-8 items-center justify-center gap-1.5 rounded-md border border-primary/20 bg-primary-mist px-2.5 text-[12px] font-semibold text-primary-pressed transition-colors hover:border-primary/35 hover:bg-[#D5ECE8] ${focusRing}`;

export const btnDanger =
    `inline-flex min-h-8 items-center justify-center gap-1.5 rounded-md border border-[#E9BEB8] bg-[#FFF4F1] px-2.5 text-[12px] font-semibold text-[#A43B2F] transition-colors hover:bg-[#FDE8E4] ${focusRing}`;

export const btnLink =
    `inline-flex items-center gap-1 text-sm font-semibold text-primary-pressed underline-offset-4 hover:underline ${focusRing}`;

export const btnXs =
    `inline-flex min-h-8 items-center justify-center gap-1 rounded-md border border-primary/20 bg-primary-mist px-2.5 text-xs font-semibold text-primary-pressed transition-colors hover:border-primary/35 hover:bg-[#D5ECE8] ${focusRing}`;

export const card = 'admin-panel bg-canvas';
export const cardPad = `${card} p-5 sm:p-6`;
export const formCard = `${card} max-w-3xl p-5 sm:p-7`;

export const pageHeader =
    'admin-page-header flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between';
export const pageEyebrow =
    'hidden';
export const pageTitle =
    'text-2xl font-semibold tracking-[-0.025em] text-ink sm:text-[28px]';
export const pageDescription =
    'mt-1.5 max-w-2xl text-sm leading-6 text-steel';
export const sectionTitle = 'text-base font-semibold tracking-[-0.01em] text-ink';
export const sectionDescription = 'mt-1 text-sm leading-5 text-steel';
export const fieldError = 'mt-1.5 text-xs font-medium text-[#A43B2F]';

const badgeBase =
    'inline-flex min-h-6 items-center rounded-md border px-2 py-0.5 text-xs font-semibold';
export const badgeOk = `${badgeBase} border-[#B9DDC5] bg-[#EAF7EE] text-[#246A3C]`;
export const badgeInfo = `${badgeBase} border-[#B8D9E2] bg-[#EAF5F6] text-[#17606A]`;
export const badgeWarn = `${badgeBase} border-[#F0CAA1] bg-[#FFF4E8] text-[#8A4300]`;
export const badgePending = `${badgeBase} border-hairline bg-surface-soft text-charcoal`;

export const tableFrame = 'admin-table-frame overflow-hidden';
export const tableScroller = 'admin-table-scroll overflow-x-auto';
export const tableClass = 'admin-table w-full text-sm';
