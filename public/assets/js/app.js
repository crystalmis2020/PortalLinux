$(function () {
    "use strict";

    const root = $("html");
    const storageKey = "support-portal-theme";
    const themeClasses = ["light-theme", "dark-theme", "semi-dark", "minimal-theme"];
    const themeManager = window.supportPortalTheme || null;
    const sidebarClasses = [
        "sidebarcolor1",
        "sidebarcolor2",
        "sidebarcolor3",
        "sidebarcolor4",
        "sidebarcolor5",
        "sidebarcolor6",
        "sidebarcolor7",
        "sidebarcolor8",
    ];
    const headerClasses = [
        "headercolor1",
        "headercolor2",
        "headercolor3",
        "headercolor4",
        "headercolor5",
        "headercolor6",
        "headercolor7",
        "headercolor8",
    ];

    function getStoredTheme() {
        if (themeManager && typeof themeManager.getStoredTheme === "function") {
            return themeManager.getStoredTheme();
        }

        const storedTheme = window.localStorage.getItem(storageKey);

        if (themeClasses.includes(storedTheme)) {
            return storedTheme;
        }

        return "light-theme";
    }

    function persistTheme(themeName) {
        window.localStorage.setItem(storageKey, themeName);
    }

    function applyThemeClass(target, themeName) {
        if (!target || !target.length) {
            return;
        }

        target.removeClass(themeClasses.join(" "));
        target.addClass(themeName);
    }

    function applyTheme(themeName) {
        if (themeManager && typeof themeManager.applyTheme === "function") {
            themeManager.applyTheme(themeName, true);
        } else {
            applyThemeClass(root, themeName);
            applyThemeClass($("body"), themeName);
        }

        persistTheme(themeName);
        syncThemeControls(themeName);
    }

    function syncThemeControls(themeName) {
        const isDark = themeName === "dark-theme";

        $(".dark-mode-icon i").attr("class", isDark ? "bx bx-sun" : "bx bx-moon");
        $("#lightmode").prop("checked", themeName === "light-theme");
        $("#darkmode").prop("checked", themeName === "dark-theme");
        $("#semidark").prop("checked", themeName === "semi-dark");
        $("#minimaltheme").prop("checked", themeName === "minimal-theme");
    }

    function setSidebarTheme(sidebarClass) {
        root.removeClass(sidebarClasses.join(" "));
        root.addClass("color-sidebar");
        root.addClass(sidebarClass);
    }

    function setHeaderTheme(headerClass) {
        root.removeClass(headerClasses.join(" "));
        root.addClass("color-header");
        root.addClass(headerClass);
    }

    applyTheme(getStoredTheme());

    new PerfectScrollbar(".header-notifications-list");

    $(".mobile-search-icon").on("click", function () {
        $(".search-bar").addClass("full-search-bar");
    });

    $(".search-close").on("click", function () {
        $(".search-bar").removeClass("full-search-bar");
    });

    $(".mobile-toggle-menu").on("click", function () {
        $(".wrapper").addClass("toggled");
    });

    $(".dark-mode-icon").on("click", function (event) {
        event.preventDefault();
        const nextTheme = root.hasClass("dark-theme") ? "light-theme" : "dark-theme";
        applyTheme(nextTheme);
    });

    $(".toggle-icon").on("click", function () {
        if ($(".wrapper").hasClass("toggled")) {
            $(".wrapper").removeClass("toggled");
            $(".sidebar-wrapper").unbind("hover");
            return;
        }

        $(".wrapper").addClass("toggled");
        $(".sidebar-wrapper").hover(
            function () {
                $(".wrapper").addClass("sidebar-hovered");
            },
            function () {
                $(".wrapper").removeClass("sidebar-hovered");
            }
        );
    });

    $(document).ready(function () {
        $(window).on("scroll", function () {
            if ($(this).scrollTop() > 300) {
                $(".back-to-top").fadeIn();
                return;
            }

            $(".back-to-top").fadeOut();
        });

        $(".back-to-top").on("click", function () {
            $("html, body").animate({
                scrollTop: 0,
            }, 600);

            return false;
        });
    });

    $(function () {
        for (
            var currentLocation = window.location,
                activeMenu = $(".metismenu li a").filter(function () {
                    return this.href === currentLocation;
                }).addClass("").parent().addClass("mm-active");
            activeMenu.is("li");

        ) {
            activeMenu = activeMenu.parent("").addClass("mm-show").parent("").addClass("mm-active");
        }
    });

    $(function () {
        $("#menu").metisMenu();
    });

    $(".chat-toggle-btn").on("click", function () {
        $(".chat-wrapper").toggleClass("chat-toggled");
    });

    $(".chat-toggle-btn-mobile").on("click", function () {
        $(".chat-wrapper").removeClass("chat-toggled");
    });

    $(".email-toggle-btn").on("click", function () {
        $(".email-wrapper").toggleClass("email-toggled");
    });

    $(".email-toggle-btn-mobile").on("click", function () {
        $(".email-wrapper").removeClass("email-toggled");
    });

    $(".compose-mail-btn").on("click", function () {
        $(".compose-mail-popup").show();
    });

    $(".compose-mail-close").on("click", function () {
        $(".compose-mail-popup").hide();
    });

    $(".switcher-btn").on("click", function () {
        $(".switcher-wrapper").toggleClass("switcher-toggled");
    });

    $(".close-switcher").on("click", function () {
        $(".switcher-wrapper").removeClass("switcher-toggled");
    });

    $("#lightmode").on("click", function () {
        applyTheme("light-theme");
    });

    $("#darkmode").on("click", function () {
        applyTheme("dark-theme");
    });

    $("#semidark").on("click", function () {
        applyTheme("semi-dark");
    });

    $("#minimaltheme").on("click", function () {
        applyTheme("minimal-theme");
    });

    $("#headercolor1").on("click", function () { setHeaderTheme("headercolor1"); });
    $("#headercolor2").on("click", function () { setHeaderTheme("headercolor2"); });
    $("#headercolor3").on("click", function () { setHeaderTheme("headercolor3"); });
    $("#headercolor4").on("click", function () { setHeaderTheme("headercolor4"); });
    $("#headercolor5").on("click", function () { setHeaderTheme("headercolor5"); });
    $("#headercolor6").on("click", function () { setHeaderTheme("headercolor6"); });
    $("#headercolor7").on("click", function () { setHeaderTheme("headercolor7"); });
    $("#headercolor8").on("click", function () { setHeaderTheme("headercolor8"); });

    $("#sidebarcolor1").on("click", function () { setSidebarTheme("sidebarcolor1"); });
    $("#sidebarcolor2").on("click", function () { setSidebarTheme("sidebarcolor2"); });
    $("#sidebarcolor3").on("click", function () { setSidebarTheme("sidebarcolor3"); });
    $("#sidebarcolor4").on("click", function () { setSidebarTheme("sidebarcolor4"); });
    $("#sidebarcolor5").on("click", function () { setSidebarTheme("sidebarcolor5"); });
    $("#sidebarcolor6").on("click", function () { setSidebarTheme("sidebarcolor6"); });
    $("#sidebarcolor7").on("click", function () { setSidebarTheme("sidebarcolor7"); });
    $("#sidebarcolor8").on("click", function () { setSidebarTheme("sidebarcolor8"); });
});
