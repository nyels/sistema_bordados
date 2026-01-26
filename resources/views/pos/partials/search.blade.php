{{-- Product Search - Premium Apple/SaaS Style --}}
<div class="pos-search-container">
    <div class="pos-search-wrapper">
        <div class="pos-search-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text"
               id="pos-search"
               placeholder="Buscar producto por nombre o SKU..."
               class="pos-search-input"
               autocomplete="off">
        <div class="pos-search-shortcut">
            <kbd>Ctrl</kbd> + <kbd>K</kbd>
        </div>
    </div>
</div>

@push('styles')
<style>
    .pos-search-container {
        padding: 20px 24px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
    }

    .pos-search-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        max-width: 100%;
    }

    .pos-search-icon {
        position: absolute;
        left: 16px;
        width: 22px;
        height: 22px;
        color: #94a3b8;
        pointer-events: none;
        z-index: 1;
    }

    .pos-search-icon svg {
        width: 100%;
        height: 100%;
    }

    .pos-search-input {
        width: 100%;
        height: 52px;
        padding: 0 120px 0 52px;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 500;
        color: #1e293b;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.03);
        transition: all 0.2s ease;
    }

    .pos-search-input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    .pos-search-input:hover {
        border-color: #cbd5e1;
    }

    .pos-search-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .pos-search-shortcut {
        position: absolute;
        right: 16px;
        display: flex;
        align-items: center;
        gap: 4px;
        color: #94a3b8;
        font-size: 12px;
        pointer-events: none;
    }

    .pos-search-shortcut kbd {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 22px;
        padding: 0 6px;
        background: linear-gradient(180deg, #fff 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        font-family: inherit;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    @media (max-width: 640px) {
        .pos-search-container {
            padding: 16px;
        }
        .pos-search-input {
            height: 48px;
            padding-right: 16px;
            font-size: 15px;
        }
        .pos-search-shortcut {
            display: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Keyboard shortcut Ctrl+K for search
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('pos-search').focus();
        }
    });
</script>
@endpush
