<style>
    /* ==========================================================================
       VetBook Admin — Medical Records Stylesheet
       ========================================================================== */

    /* ---------- Header & Navigation ---------- */
    .mr-subtitle {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #6B6358;
        margin: 0;
    }
    .branch-note {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
        padding: 5px 14px;
        border-radius: 9999px;
        background: #C8E8D0;
        color: #2A6038;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
    }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        color: #4A7C59;
        text-decoration: none;
        margin-bottom: 16px;
        transition: color 0.15s ease;
    }
    .back-link:hover {
        color: #2A6038;
        text-decoration: underline;
    }
    .btn-green {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 9999px;
        background: #4A7C59;
        color: #FFFFFF;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        border: none;
        box-shadow: 0 4px 14px rgba(74, 124, 89, 0.3);
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .btn-green:hover {
        background: #3D6649;
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(74, 124, 89, 0.35);
        text-decoration: none;
    }
    .btn-soft {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        border-radius: 9999px;
        background: #FAF6F0;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid rgba(196, 200, 188, 0.5);
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .btn-soft:hover {
        background: #EAE6DE;
        color: #2E3230;
        text-decoration: none;
    }
    .btn-danger-soft {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        border-radius: 9999px;
        background: #FFDAD8;
        color: #B83230;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid rgba(184, 50, 48, 0.2);
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .btn-danger-soft:hover {
        background: #B83230;
        color: #FFFFFF;
        text-decoration: none;
    }
    .clear-link {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        color: #B83230;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 600;
        padding: 6px 12px;
        transition: color 0.15s ease;
    }
    .clear-link:hover {
        text-decoration: underline;
        color: #9A2826;
    }

    /* ---------- Statistics Grid ---------- */
    .mr-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .mr-stat {
        background: #FFFFFF;
        border: 1px solid rgba(196, 200, 188, 0.3);
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(46, 50, 48, 0.05);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .mr-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 26px rgba(46, 50, 48, 0.08);
    }
    .mr-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .mr-stat-value {
        font-family: 'Literata', serif;
        font-size: 28px;
        font-weight: 700;
        color: #2E3230;
        line-height: 1;
    }
    .mr-stat-label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 600;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 6px;
    }

    /* ---------- Filter and Search Panel ---------- */
    .filter-panel {
        background: #FFFFFF;
        border: 1px solid rgba(196, 200, 188, 0.3);
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(46, 50, 48, 0.05);
        margin-bottom: 24px;
    }
    .filter-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .filter-tab {
        padding: 8px 18px;
        border-radius: 9999px;
        background: #F5F1EA;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filter-tab:hover {
        background: #EAE6DE;
        color: #2E3230;
        text-decoration: none;
    }
    .filter-tab.active {
        background: #4A7C59;
        color: #FFFFFF;
    }
    .search-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }
    .search-row .input-wrap {
        position: relative;
        flex: 1;
        min-width: 240px;
    }
    .search-row .input-wrap i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B6358;
        font-size: 14px;
        pointer-events: none;
    }
    .search-row input[type="text"] {
        width: 100%;
        height: 44px;
        padding: 10px 16px 10px 42px;
        border-radius: 16px;
        border: 1px solid rgba(196, 200, 188, 0.6);
        background: #FAF6F0;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #2E3230;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .search-row input[type="text"]:focus {
        outline: none;
        border-color: #4A7C59;
        box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.12);
        background: #FFFFFF;
    }
    .search-row select {
        height: 44px;
        padding: 10px 16px;
        border-radius: 16px;
        border: 1px solid rgba(196, 200, 188, 0.6);
        background: #FAF6F0;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #2E3230;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        min-width: 180px;
        cursor: pointer;
    }
    .search-row select:focus {
        outline: none;
        border-color: #4A7C59;
        box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.12);
        background: #FFFFFF;
    }
    .search-row button[type="submit"] {
        height: 44px;
        padding: 0 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* ---------- Medical Card Container ---------- */
    .mr-card {
        background: #FFFFFF;
        border: 1px solid rgba(196, 200, 188, 0.3);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(46, 50, 48, 0.05);
        margin-bottom: 24px;
    }
    .mr-card h3 {
        font-family: 'Literata', serif;
        font-size: 18px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
    }

    /* ---------- Timeline & Record Cards ---------- */
    .tl-month {
        font-family: 'Literata', serif;
        font-size: 20px;
        font-weight: 700;
        color: #4A7C59;
        margin: 32px 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tl {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 24px;
    }
    .tl-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }
    .tl-date {
        width: 58px;
        padding: 10px 4px;
        border-radius: 16px;
        background: #FFFFFF;
        border: 1px solid rgba(196, 200, 188, 0.4);
        box-shadow: 0 2px 10px rgba(46, 50, 48, 0.04);
        text-align: center;
        flex-shrink: 0;
    }
    .tl-date .d {
        font-family: 'Literata', serif;
        font-size: 20px;
        font-weight: 700;
        color: #2E3230;
        line-height: 1;
    }
    .tl-date .m {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 700;
        color: #6B6358;
        text-transform: uppercase;
        margin-top: 4px;
        letter-spacing: 0.5px;
    }
    .tl-card {
        flex: 1;
        background: #FFFFFF;
        border: 1px solid rgba(196, 200, 188, 0.3);
        border-radius: 20px;
        padding: 20px 24px;
        box-shadow: 0 4px 16px rgba(46, 50, 48, 0.04);
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .tl-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(46, 50, 48, 0.08);
        border-color: rgba(74, 124, 89, 0.35);
    }
    .tl-pet-name {
        font-family: 'Literata', serif;
        font-size: 17px;
        font-weight: 700;
        color: #2E3230;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .tl-pet-name:hover {
        color: #4A7C59;
        text-decoration: none;
    }
    .tl-sub {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        color: #6B6358;
        margin-top: 2px;
    }
    .tl-diagnosis {
        background: #FAF6F0;
        border-radius: 14px;
        padding: 12px 16px;
        margin: 12px 0;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #2E3230;
        line-height: 1.5;
    }
    .tl-diagnosis .lbl {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #4A7C59;
        margin-bottom: 3px;
    }
    .tl-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #FAF6F0;
        font-family: 'Nunito Sans', sans-serif;
    }
    .tl-link {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 700;
        color: #4A7C59;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: color 0.15s ease;
    }
    .tl-link:hover {
        color: #2A6038;
        text-decoration: none;
    }

    /* ---------- Status Chips & Pills ---------- */
    .vax-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
        margin-right: 6px;
        margin-bottom: 6px;
    }
    .vax-pill.ok {
        background: #C8E8D0;
        color: #2A6038;
    }
    .vax-pill.soon {
        background: #F8E0A8;
        color: #705C30;
    }
    .vax-pill.overdue {
        background: #FFDAD8;
        color: #B83230;
    }
    .vax-pill small {
        font-weight: 500;
        opacity: 0.85;
        margin-left: 4px;
    }
    .next-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 700;
        margin-right: 6px;
    }
    .next-pill.upcoming {
        background: #F8E0A8;
        color: #705C30;
    }
    .next-pill.past {
        background: #EAE6DE;
        color: #6B6358;
    }
    .branch-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 9999px;
        background: #FAF6F0;
        color: #6B6358;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid rgba(196, 200, 188, 0.4);
        margin-right: 4px;
    }
    .branch-chip.mine {
        background: #C8E8D0;
        color: #2A6038;
        border-color: rgba(42, 96, 56, 0.3);
    }

    /* ---------- Empty State ---------- */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: #FAF6F0;
        color: #6B6358;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin-bottom: 16px;
    }
    .empty-state h4 {
        font-family: 'Literata', serif;
        font-size: 20px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 8px;
    }
    .empty-state p {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #6B6358;
        max-width: 420px;
        margin: 0 auto 20px;
        line-height: 1.5;
    }

    /* ---------- Pet Avatar Component ---------- */
    .pet-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        overflow: hidden;
        position: relative;
        font-family: 'Literata', serif;
        font-weight: 700;
        flex-shrink: 0;
    }
    .pet-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }

    /* ---------- Pagination ---------- */
    .pagination-wrap {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 24px;
        padding: 16px 20px;
        background: #FFFFFF;
        border-radius: 20px;
        border: 1px solid rgba(196, 200, 188, 0.3);
        box-shadow: 0 4px 16px rgba(46, 50, 48, 0.04);
        font-family: 'Nunito Sans', sans-serif;
    }
    .pagination-info {
        font-size: 13px;
        color: #6B6358;
        font-weight: 600;
    }

    /* ---------- Responsive Adjustments ---------- */
    @media (max-width: 992px) {
        .mr-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
        .mr-stats {
            grid-template-columns: 1fr;
        }
        .search-row {
            flex-direction: column;
            align-items: stretch;
        }
        .search-row .input-wrap,
        .search-row select,
        .search-row button[type="submit"] {
            width: 100%;
        }
        .tl-item {
            flex-direction: column;
            gap: 8px;
        }
        .tl-date {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 6px;
        }
        .tl-date .m {
            margin-top: 0;
        }
    }
</style>
