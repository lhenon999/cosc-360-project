document.addEventListener("DOMContentLoaded", function () {
    const darkModeToggle = document.getElementById("darkModeToggle");

    fetch("get_dark_mode.php")
        .then(response => response.json())
        .then(data => {
            let darkModePHP = data.dark_mode;
            let darkModeLocal = localStorage.getItem("darkMode") === "enabled";

            if (darkModePHP || darkModeLocal) {
                document.body.classList.add("bg-dark", "text-light");
                if (darkModeToggle) darkModeToggle.checked = true;
            } else {
                document.body.classList.remove("bg-dark", "text-light");
                if (darkModeToggle) darkModeToggle.checked = false;
            }
        })
        .catch(error => console.error("Error fetching dark mode:", error));

    if (darkModeToggle) {
        darkModeToggle.addEventListener("change", function () {
            let darkMode = this.checked ? 1 : 0;

            document.body.classList.toggle("bg-dark", darkMode);
            document.body.classList.toggle("text-light", darkMode);

            localStorage.setItem("darkMode", darkMode ? "enabled" : "disabled");

            fetch("update_profile.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "dark_mode=" + darkMode
            })
            .then(response => response.json())
            .then(data => console.log("Dark mode updated:", data))
            .catch(error => console.error("Error updating dark mode:", error));
        });
    }
});
