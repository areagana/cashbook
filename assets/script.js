const hamburger = document.querySelector('#toggle-btn');
hamburger.addEventListener('click',function(){
    document.querySelector('#sidebar').classList.toggle('expand');
});


$(document).ready(function () 
{

    const sidebar =
        $('#sidebar');

    const overlay =
        $('#sidebar-overlay');

    const mobileMenuBtn =
        $('#mobile-menu-btn');

    const mobileCloseBtn =
        $('#mobile-close-btn');

    const toggleBtn =
        $('#toggle-btn');


    /*
    ==================================================
    CLOSE MOBILE SIDEBAR
    ==================================================
    */

    function closeMobileSidebar()
    {
        sidebar.removeClass('expand');

        overlay.removeClass('active');

        mobileMenuBtn.attr(
            'aria-expanded',
            'false'
        );

        $('body').removeClass(
            'sidebar-open'
        );
    }


    /*
    ==================================================
    OPEN MOBILE SIDEBAR
    ==================================================
    */

    function openMobileSidebar()
    {
        sidebar.addClass('expand');

        overlay.addClass('active');

        mobileMenuBtn.attr(
            'aria-expanded',
            'true'
        );

        $('body').addClass(
            'sidebar-open'
        );
    }


    /*
    ==================================================
    DESKTOP SIDEBAR TOGGLE
    ==================================================
    */

    toggleBtn.on(
        'click',
        function () {

            if (
                window.innerWidth <= 990
            ) {

                closeMobileSidebar();

                return;
            }


            sidebar.toggleClass(
                'expand'
            );


            /*
            ------------------------------------------
            SAVE DESKTOP STATE
            ------------------------------------------
            */

            if (
                sidebar.hasClass(
                    'expand'
                )
            ) {

                localStorage.setItem(
                    'cashbook_sidebar',
                    'expanded'
                );

                toggleBtn.attr(
                    'aria-expanded',
                    'true'
                );

            } else {

                localStorage.setItem(
                    'cashbook_sidebar',
                    'collapsed'
                );

                toggleBtn.attr(
                    'aria-expanded',
                    'false'
                );
            }

        }
    );


    /*
    ==================================================
    MOBILE OPEN
    ==================================================
    */

    mobileMenuBtn.on(
        'click',
        function () {

            openMobileSidebar();

        }
    );


    /*
    ==================================================
    MOBILE CLOSE
    ==================================================
    */

    mobileCloseBtn.on(
        'click',
        function () {

            closeMobileSidebar();

        }
    );


    /*
    ==================================================
    CLICK OVERLAY
    ==================================================
    */

    overlay.on(
        'click',
        function () {

            closeMobileSidebar();

        }
    );


    /*
    ==================================================
    CLOSE WHEN LINK IS CLICKED ON MOBILE
    ==================================================
    */

    sidebar.on(
        'click',
        '.sidebar-link',
        function () {

            if (
                window.innerWidth <= 990
            ) {

                closeMobileSidebar();

            }

        }
    );


    /*
    ==================================================
    ESC KEY
    ==================================================
    */

    $(document).on(
        'keydown',
        function (e) {

            if (
                e.key === 'Escape'
            ) {

                closeMobileSidebar();

            }

        }
    );


    /*
    ==================================================
    RESTORE DESKTOP SIDEBAR STATE
    ==================================================
    */

    function restoreSidebarState()
    {
        if (
            window.innerWidth > 990
        ) {

            const state =
                localStorage.getItem(
                    'cashbook_sidebar'
                );


            /*
            ------------------------------------------
            DEFAULT = EXPANDED
            ------------------------------------------
            */

            if (
                state === 'collapsed'
            ) {

                sidebar.removeClass(
                    'expand'
                );

            } else {

                sidebar.addClass(
                    'expand'
                );
            }


            overlay.removeClass(
                'active'
            );

            $('body').removeClass(
                'sidebar-open'
            );

        } else {

            /*
            ------------------------------------------
            MOBILE STARTS CLOSED
            ------------------------------------------
            */

            sidebar.removeClass(
                'expand'
            );

            overlay.removeClass(
                'active'
            );

            $('body').removeClass(
                'sidebar-open'
            );
        }
    }


    restoreSidebarState();

    /*
    ==================================================
    WINDOW RESIZE
    ==================================================
    */

    let previousWidth =
        window.innerWidth;

    $(window).on(
        'resize',
        function () {

            const currentWidth =
                window.innerWidth;


            /*
            ------------------------------------------
            Desktop → Mobile
            ------------------------------------------
            */

            if (
                previousWidth > 990 &&
                currentWidth <= 990
            ) {

                closeMobileSidebar();

            }


            /*
            ------------------------------------------
            Mobile → Desktop
            ------------------------------------------
            */

            if (
                previousWidth <= 990 &&
                currentWidth > 990
            ) {

                closeMobileSidebar();

                restoreSidebarState();

            }


            previousWidth =
                currentWidth;
        }
    );

});