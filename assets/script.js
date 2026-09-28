/*
==========================================================
FR(U)-CASHBOOK GLOBAL SCRIPT
==========================================================

DESKTOP:
    .expand = expanded sidebar

MOBILE:
    .mobile-open = sidebar visible

OVERLAY:
    .active = overlay visible

This file uses ONE sidebar controller only.
==========================================================
*/

(function () {

    'use strict';


    document.addEventListener('DOMContentLoaded', function () {

        /*
        ======================================================
        GET SIDEBAR ELEMENTS
        ======================================================
        */

        const mobileMenuBtn =
            document.getElementById('mobile-menu-btn');

        const mobileCloseBtn =
            document.getElementById('mobile-close-btn');

        const sidebar =
            document.getElementById('sidebar');

        const overlay =
            document.getElementById('sidebar-overlay');


        if (mobileMenuBtn && sidebar) {

            mobileMenuBtn.addEventListener(
                'click',
                function (e) {

                    e.preventDefault();

                    sidebar.classList.add(
                        'mobile-open'
                    );

                    if (overlay) {
                        overlay.classList.add(
                            'active'
                        );
                    }

                    document.body.classList.add(
                        'sidebar-open'
                    );

                }
            );

        }


        function closeMobileSidebar()
        {
            if (sidebar) {

                sidebar.classList.remove(
                    'mobile-open'
                );

            }

            if (overlay) {

                overlay.classList.remove(
                    'active'
                );

            }

            document.body.classList.remove(
                'sidebar-open'
            );
        }


        if (mobileCloseBtn) {

            mobileCloseBtn.addEventListener(
                'click',
                closeMobileSidebar
            );

        }


        if (overlay) {

            overlay.addEventListener(
                'click',
                closeMobileSidebar
            );

        }


        /*
        ======================================================
        RESTORE DESKTOP SIDEBAR STATE
        ======================================================
        */

        function restoreDesktopSidebar()
        {

            /*
            Mobile always starts closed.
            */

            if (isMobile()) {

                closeMobileSidebar();

                return;
            }


            /*
            Ensure mobile classes are cleared.
            */

            closeMobileSidebar();


            /*
            Get previous desktop preference.
            */

            const state =
                localStorage.getItem(
                    STORAGE_KEY
                );


            /*
            Default desktop state = expanded.
            */

            if (state === 'collapsed') {

                sidebar.classList.remove(
                    'expand'
                );

            } else {

                sidebar.classList.add(
                    'expand'
                );

            }


            updateDesktopAria();
        }


        /*
        ======================================================
        DESKTOP TOGGLE BUTTON
        ======================================================
        */

        if (toggleBtn) {

            toggleBtn.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();


                    /*
                    On mobile the desktop toggle should not
                    control the drawer.
                    */

                    if (isMobile()) {

                        closeMobileSidebar();

                        return;
                    }


                    /*
                    Toggle desktop sidebar.
                    */

                    sidebar.classList.toggle(
                        'expand'
                    );


                    /*
                    Save preference.
                    */

                    const state =
                        sidebar.classList.contains(
                            'expand'
                        )
                            ? 'expanded'
                            : 'collapsed';


                    localStorage.setItem(
                        STORAGE_KEY,
                        state
                    );


                    updateDesktopAria();

                }
            );

        }


        /*
        ======================================================
        MOBILE MENU BUTTON
        ======================================================
        */

        if (mobileMenuBtn) {

            mobileMenuBtn.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();

                    openMobileSidebar();

                }
            );

        }


        /*
        ======================================================
        MOBILE CLOSE BUTTON
        ======================================================
        */

        if (mobileCloseBtn) {

            mobileCloseBtn.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();

                    closeMobileSidebar();

                }
            );

        }


        /*
        ======================================================
        CLICK OVERLAY TO CLOSE
        ======================================================
        */

        if (overlay) {

            overlay.addEventListener(
                'click',
                function () {

                    closeMobileSidebar();

                }
            );

        }


        /*
        ======================================================
        CLOSE AFTER CLICKING SIDEBAR LINK ON MOBILE
        ======================================================
        */

        sidebar.addEventListener(
            'click',
            function (event) {

                if (!isMobile()) {
                    return;
                }


                const link =
                    event.target.closest(
                        '.sidebar-link'
                    );


                if (link) {

                    closeMobileSidebar();

                }

            }
        );


        /*
        ======================================================
        ESCAPE KEY
        ======================================================
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    isMobile()
                ) {

                    closeMobileSidebar();

                }

            }
        );


        /*
        ======================================================
        WINDOW RESIZE
        ======================================================

        We only react when crossing between desktop/mobile.
        This avoids repeatedly resetting the sidebar when the
        browser changes size by only a few pixels.
        ======================================================
        */

        let previousMobileState =
            isMobile();


        window.addEventListener(
            'resize',
            function () {

                const currentMobileState =
                    isMobile();


                /*
                No breakpoint change.
                */

                if (
                    currentMobileState ===
                    previousMobileState
                ) {

                    return;

                }


                /*
                Desktop -> Mobile
                */

                if (currentMobileState) {

                    closeMobileSidebar();

                }


                /*
                Mobile -> Desktop
                */

                else {

                    closeMobileSidebar();

                    restoreDesktopSidebar();

                }


                previousMobileState =
                    currentMobileState;

            }
        );


        /*
        ======================================================
        INITIALISE SIDEBAR
        ======================================================
        */

        if (isMobile()) {

            /*
            Mobile starts closed.
            */

            closeMobileSidebar();

        } else {

            /*
            Desktop restores saved state.
            */

            restoreDesktopSidebar();

        }

    });

})();

window.printStatement = function(printableId, title)
{
        const printable =
            document.getElementById(printableId);

        if(!printable)
        {
            xdialog.alert(
                'The printable statement could not be found.'
            );

            return;
        }


        const printWindow =
            window.open(
                '',
                '_blank',
                'width=1000,height=800'
            );


        if(!printWindow)
        {
            xdialog.alert(
                'Please allow pop-ups in your browser to print this statement.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | COPY STATEMENT
        |--------------------------------------------------------------------------
        */

        const statementHtml =  printable.outerHTML;


        /*
        |--------------------------------------------------------------------------
        | PRINT DOCUMENT
        |--------------------------------------------------------------------------
        */

        printWindow.document.open();


        printWindow.document.write(`
            <!DOCTYPE html>

            <html>

            <head>

                <meta charset="UTF-8">

                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1"
                >

                <title>${title || 'Balance Statement'}</title>


                <style>

                    * {
                        box-sizing: border-box;
                    }


                    html,
                    body {
                        margin: 0;
                        padding: 0;

                        width: 100%;

                        height: auto !important;

                        overflow: visible !important;

                        background: #ffffff;

                        color: #263238;

                        font-family:
                            Arial,
                            Helvetica,
                            sans-serif;

                        font-size: 11px;
                    }


                    body {
                        padding: 0;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PRINTABLE CONTAINER
                    |--------------------------------------------------------------------------
                    */

                    #routePrintable,
                    #managerPrintable {

                        width: 100% !important;

                        height: auto !important;

                        max-height: none !important;

                        overflow: visible !important;

                        margin: 0 !important;

                        padding: 0 !important;

                        background: #ffffff;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | DOCUMENT HEADER
                    |--------------------------------------------------------------------------
                    */

                    .route-document-header,
                    .manager-document-header {

                        display: flex;

                        justify-content:
                            space-between;

                        align-items:
                            flex-start;

                        gap: 20px;

                        padding-bottom: 14px;

                        margin-bottom: 15px;

                        border-bottom:
                            2px solid #172b3a;
                    }


                    .route-document-title h2,
                    .manager-document-title h2 {

                        margin:
                            0 0 5px;

                        color: #172b3a;

                        font-size: 19px;

                        font-weight: 700;

                        text-transform:
                            uppercase;
                    }


                    .route-document-title p,
                    .manager-document-title p {

                        margin: 0;

                        color: #718096;

                        font-size: 10px;
                    }


                    .route-document-meta,
                    .manager-document-meta {

                        min-width: 180px;

                        text-align: right;

                        color: #718096;

                        font-size: 10px;
                    }


                    .route-document-meta strong,
                    .manager-document-meta strong {

                        color: #172b3a;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | INFORMATION GRID
                    |--------------------------------------------------------------------------
                    */

                    .route-info-grid,
                    .manager-info-grid {

                        display: grid;

                        grid-template-columns:
                            repeat(3, 1fr);

                        gap: 8px;

                        margin-bottom: 14px;
                    }


                    .route-info-item,
                    .manager-info-item {

                        padding:
                            9px 10px;

                        background: #f8fafb;

                        border:
                            1px solid #edf1f4;
                    }


                    .route-info-label,
                    .manager-info-label {

                        display: block;

                        margin-bottom: 3px;

                        color: #8492a6;

                        font-size: 8px;

                        font-weight: 700;

                        text-transform:
                            uppercase;
                    }


                    .route-info-value,
                    .manager-info-value {

                        color: #172b3a;

                        font-size: 11px;

                        font-weight: 700;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SUMMARY CARDS
                    |--------------------------------------------------------------------------
                    */

                    .route-balance-summary,
                    .manager-balance-summary {

                        display: grid;

                        grid-template-columns:
                            repeat(4, 1fr);

                        gap: 8px;

                        margin-bottom: 18px;

                        page-break-inside:
                            avoid;

                        break-inside:
                            avoid;
                    }


                    .route-balance-card,
                    .manager-balance-card {

                        padding: 10px;

                        border:
                            1px solid #e5eaee;

                        background: #ffffff;
                    }


                    .route-balance-card span,
                    .manager-balance-card span {

                        display: block;

                        margin-bottom: 3px;

                        color: #8492a6;

                        font-size: 8px;

                        font-weight: 700;

                        text-transform:
                            uppercase;
                    }


                    .route-balance-card strong,
                    .manager-balance-card strong {

                        color: #172b3a;

                        font-size: 14px;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | ROUTE SECTIONS
                    |--------------------------------------------------------------------------
                    */

                    .manager-route-section {

                        width: 100%;

                        margin-bottom: 18px;

                        border:
                            1px solid #dfe5e9;

                        page-break-inside:
                            auto;

                        break-inside:
                            auto;
                    }


                    .manager-route-header {

                        display: flex;

                        justify-content:
                            space-between;

                        align-items:
                            center;

                        gap: 15px;

                        padding:
                            9px 10px;

                        background: #f3f6f8;

                        border-bottom:
                            1px solid #dfe6eb;

                        page-break-after:
                            avoid;

                        break-after:
                            avoid-page;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | TABLE CONTAINERS
                    |--------------------------------------------------------------------------
                    |
                    | This is especially important because the original statement
                    | lives inside a scrollable modal.
                    |
                    |--------------------------------------------------------------------------
                    */

                    .manager-table-container,
                    .route-table-container,
                    .table-responsive {

                        width: 100% !important;

                        height: auto !important;

                        max-height: none !important;

                        overflow: visible !important;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | TABLES
                    |--------------------------------------------------------------------------
                    */

                    .manager-customer-table,
                    .route-customer-table {

                        width: 100% !important;

                        border-collapse:
                            collapse;

                        table-layout: auto;
                    }


                    .manager-customer-table thead,
                    .route-customer-table thead {

                        display:
                            table-header-group;
                    }


                    .manager-customer-table th,
                    .route-customer-table th {

                        padding: 6px;

                        border:
                            1px solid #dfe5e9;

                        background: #f8fafb;

                        color: #4a5568;

                        font-size: 8px;

                        font-weight: 700;

                        text-align: left;

                        text-transform:
                            uppercase;
                    }


                    .manager-customer-table td,
                    .route-customer-table td {

                        padding: 6px;

                        border:
                            1px solid #e5eaee;

                        color: #4a5568;

                        font-size: 9px;
                    }


                    .manager-customer-table tr,
                    .route-customer-table tr {

                        page-break-inside:
                            avoid;

                        break-inside:
                            avoid;
                    }


                    .customer-name {

                        color: #172b3a;

                        font-weight: 700;
                    }


                    .amount {

                        text-align: right;

                        white-space: nowrap;

                        font-weight: 700;
                    }


                    .owing {

                        color: #c96b13;
                    }


                    .cleared {

                        color: #087f5b;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | STATUS
                    |--------------------------------------------------------------------------
                    */

                    .manager-balance-status,
                    .route-balance-status {

                        display:
                            inline-block;

                        padding:
                            2px 5px;

                        font-size: 7px;

                        font-weight: 700;

                        text-transform:
                            uppercase;
                    }


                    .manager-balance-status.owing,
                    .route-balance-status.owing {

                        background: #fff7ed;

                        color: #c96b13;
                    }


                    .manager-balance-status.cleared,
                    .route-balance-status.cleared {

                        background: #ecfdf5;

                        color: #087f5b;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | TOTALS
                    |--------------------------------------------------------------------------
                    */

                    .manager-route-footer,
                    .route-total-row {

                        page-break-inside:
                            avoid;

                        break-inside:
                            avoid;
                    }


                    .manager-grand-total,
                    .route-grand-total {

                        display: flex;

                        justify-content:
                            space-between;

                        align-items: center;

                        margin-top: 20px;

                        padding:
                            13px 15px;

                        background:
                            #172b3a !important;

                        color:
                            #ffffff !important;

                        page-break-inside:
                            avoid;

                        break-inside:
                            avoid;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | STATEMENT FOOTER
                    |--------------------------------------------------------------------------
                    */

                    .manager-statement-footer,
                    .route-statement-footer {

                        display: flex;

                        justify-content:
                            space-between;

                        margin-top: 22px;

                        padding-top: 10px;

                        border-top:
                            1px solid #dfe5e9;

                        color: #8492a6;

                        font-size: 8px;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | HIDE SCREEN-ONLY CONTROLS
                    |--------------------------------------------------------------------------
                    */

                    button,
                    .btn,
                    .manager-statement-toolbar,
                    .route-statement-toolbar {

                        display:
                            none !important;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PRINT
                    |--------------------------------------------------------------------------
                    */

                    @page {

                        size:
                            A4 portrait;

                        margin:
                            10mm;
                    }


                    @media print {

                        html,
                        body {

                            height:
                                auto !important;

                            max-height:
                                none !important;

                            overflow:
                                visible !important;
                        }


                        #routePrintable,
                        #managerPrintable {

                            height:
                                auto !important;

                            max-height:
                                none !important;

                            overflow:
                                visible !important;
                        }


                        .manager-table-container,
                        .route-table-container,
                        .table-responsive {

                            height:
                                auto !important;

                            max-height:
                                none !important;

                            overflow:
                                visible !important;
                        }


                        thead {

                            display:
                                table-header-group;
                        }


                        tfoot {

                            display:
                                table-footer-group;
                        }


                        tr {

                            page-break-inside:
                                avoid;

                            break-inside:
                                avoid;
                        }

                    }

                </style>

            </head>


            <body>

                ${statementHtml}

            </body>


            </html>
        `);


        printWindow.document.close();


        /*
        |--------------------------------------------------------------------------
        | WAIT UNTIL PRINT DOCUMENT IS READY
        |--------------------------------------------------------------------------
        */

        printWindow.onload = function()
        {

            printWindow.focus();


            setTimeout(
                function()
                {

                    printWindow.print();

                },
                250
            );

        };


        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        |
        | Some browsers do not fire onload after document.write reliably.
        |
        |--------------------------------------------------------------------------
        */

        setTimeout(
            function()
            {

                try
                {

                    if(
                        printWindow &&
                        !printWindow.closed
                    )
                    {

                        printWindow.focus();

                    }

                }
                catch(e)
                {
                    console.error(e);
                }

            },
            500
        );

    };