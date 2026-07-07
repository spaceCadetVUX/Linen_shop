{{-- Reorder Filament styles: ensure custom CSS loads after Filament core --}}

<style>
    /* ── Product edit/create — Step 2: Manage Variants ──────────────────────
       Nhiều biến thể (Color x Size...) dễ lộn dòng khi list dài — tách mỗi
       item thành 1 card riêng + đánh số thứ tự để dễ theo dõi. ──────────── */

    .variants-repeater .fi-fo-repeater-items {
        gap: 1rem;
        counter-reset: variant-index;
    }

    .variants-repeater .fi-fo-repeater-item {
        counter-increment: variant-index;
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        background: var(--gray-50);
        overflow: hidden;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .dark .variants-repeater .fi-fo-repeater-item {
        border-color: var(--gray-700);
        background: var(--gray-800);
    }
    .variants-repeater .fi-fo-repeater-item:hover {
        border-color: var(--primary-400);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    }

    .variants-repeater .fi-fo-repeater-item-header {
        position: relative;
        padding-left: 2.75rem;
    }
    .variants-repeater .fi-fo-repeater-item-header::before {
        content: counter(variant-index);
        position: absolute;
        left: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 9999px;
        background: var(--primary-100);
        color: var(--primary-700);
        font-size: 0.7rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .dark .variants-repeater .fi-fo-repeater-item-header::before {
        background: var(--primary-900);
        color: var(--primary-300);
    }

    .variants-repeater .fi-fo-repeater-item-header-label {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-weight: 600;
        letter-spacing: 0.01em;
    }

    .variants-repeater .fi-fo-repeater-item-content {
        padding-top: 0.25rem;
    }
</style>
