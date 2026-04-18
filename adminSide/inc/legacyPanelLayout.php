<style>
.legacy-wrapper {
        width: calc(100% - 240px);
        margin-left: 240px;
        padding: 4.75rem 1.5rem 2.5rem;
    }

    .legacy-surface {
        background: #fff;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        overflow-x: auto;
    }

    .legacy-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .legacy-search-row {
        display: grid;
        grid-template-columns: minmax(280px, 1fr) auto auto;
        gap: 0.85rem;
        align-items: center;
        margin-bottom: 1rem;
    }

    .legacy-table-wrap {
        overflow-x: auto;
    }

.legacy-table-wrap .table {
        min-width: 820px;
        margin-bottom: 0;
    }

    .legacy-table-wrap.narrow-table .table {
        min-width: 680px;
        width: auto;
    }

    @media (max-width: 1200px) {
        .legacy-wrapper {
            width: 100%;
            margin-left: 0;
            padding: 1rem;
        }
    }

    @media (max-width: 768px) {
        .legacy-search-row {
            grid-template-columns: 1fr;
        }
    }
</style>
