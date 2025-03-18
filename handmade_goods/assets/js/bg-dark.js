document.addEventListener("DOMContentLoaded", function () {
    const lightTheme = document.getElementById("light-theme");
    const darkTheme = document.getElementById("dark-theme");

    let darkModeLocal = localStorage.getItem("darkMode");

    if (darkModeLocal === "enabled") {
        document.body.classList.add("bg-dark", "text-light");
        darkTheme.checked = true;
        lightTheme.checked = false;
    } else {
        document.body.classList.remove("bg-dark", "text-light");
        lightTheme.checked = true;
        darkTheme.checked = false;
    }

    lightTheme.addEventListener("change", function () {
        if (lightTheme.checked) {
            document.body.classList.remove("bg-dark", "text-light");
            localStorage.setItem("darkMode", "disabled");

            darkTheme.checked = false;

            fetch("../pages/dark_mode.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "dark_mode=0"
            });
        }
    });

    darkTheme.addEventListener("change", function () {
        if (darkTheme.checked) {
            document.body.classList.add("bg-dark", "text-light");
            localStorage.setItem("darkMode", "enabled");

            lightTheme.checked = false;

            fetch("../pages/dark_mode.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "dark_mode=1"
            });
        }
    });
});