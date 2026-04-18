<style>
    .ops-wrapper {
        width: calc(100% - 240px);
        margin-left: 240px;
        padding: 4.75rem 1.5rem 2.5rem;
    }

    .ops-surface {
        background: #ffffff;
        border-radius: 18px;
        padding: 1.6rem 1.9rem 1.9rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }

    .ops-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin: 0 0 1.25rem;
        padding: 0;
    }

    .ops-head h2 {
        margin: 0 0 0.35rem;
        font-size: 2rem;
        font-weight: 500;
        line-height: 1.2;
    }

    .ops-head p {
        margin: 0.35rem 0 0;
        color: #64748b;
        max-width: 760px;
    }

    .ops-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .ops-card {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #f8fafc;
        border-radius: 18px;
        padding: 1rem 1.1rem;
    }

    .ops-card span {
        display: block;
        font-size: 0.85rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(248, 250, 252, 0.72);
    }

    .ops-card strong {
        display: block;
        margin-top: 0.35rem;
        font-size: 1.7rem;
        line-height: 1.1;
        font-weight: 600;
    }

    .ops-columns {
        display: grid;
        grid-template-columns: 1.25fr 0.95fr;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .ops-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 1.2rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        overflow-x: auto;
    }

    .ops-panel h3 {
        margin: 0 0 0.85rem;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .ops-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .ops-cta-group {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .ops-searchbar {
        display: grid;
        grid-template-columns: minmax(240px, 1.2fr) auto auto;
        gap: 0.85rem;
        align-items: center;
        margin-bottom: 1rem;
    }

    .ops-searchbar.compact {
        grid-template-columns: minmax(260px, 1fr) auto auto;
    }

    .ops-table-wrap {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
    }

    .ops-list {
        margin: 0;
        padding-left: 1rem;
        color: #475569;
    }

    .ops-list li + li {
        margin-top: 0.45rem;
    }

    .ops-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
        min-width: 720px;
    }

    .ops-table th,
    .ops-table td {
        padding: 0.75rem 0.6rem;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: top;
    }

    .ops-table th {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }

    .ops-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 0.9rem;
        align-items: end;
    }

    .ops-form .full {
        grid-column: 1 / -1;
    }

    .ops-form label {
        display: block;
        margin-bottom: 0.35rem;
        font-size: 0.88rem;
        font-weight: 500;
        color: #334155;
    }

    .ops-form input,
    .ops-form select,
    .ops-form textarea {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 0.7rem 0.8rem;
        font-size: 0.95rem;
        background: #fff;
    }

    .ops-form textarea {
        min-height: 108px;
        resize: vertical;
    }

    .ops-button {
        border: 0;
        border-radius: 999px;
        padding: 0.75rem 1rem;
        background: #0f172a;
        color: #fff;
        font-weight: 500;
    }

    .ops-button.alt {
        background: #b77935;
    }

    .ops-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .ops-badge.low {
        background: #fee2e2;
        color: #b91c1c;
    }

    .ops-badge.good {
        background: #dcfce7;
        color: #166534;
    }

    .ops-badge.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .ops-empty {
        margin: 0;
        color: #64748b;
    }

    .ops-page-title {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        letter-spacing: -0.03em;
    }

    .ops-page-copy {
        margin: 0.35rem 0 0;
        color: #64748b;
        max-width: 760px;
    }

    .ops-surface > :first-child {
        margin-top: 0;
    }

    .ops-surface > :last-child {
        margin-bottom: 0;
    }

    @media (max-width: 1200px) {
        .ops-wrapper {
            width: 100%;
            margin-left: 0;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .ops-surface {
            padding: 1.25rem;
        }
    }

    @media (max-width: 900px) {
        .ops-columns {
            grid-template-columns: 1fr;
        }

        .ops-head {
            flex-direction: column;
        }

        .ops-searchbar,
        .ops-searchbar.compact {
            grid-template-columns: 1fr;
        }
    }
</style>
