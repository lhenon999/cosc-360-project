document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.querySelector(".search-input");
    const suggestionsBox = document.createElement("div");
    suggestionsBox.id = "search-suggestions";
    suggestionsBox.style.position = "absolute";
    suggestionsBox.style.background = "#fff";
    suggestionsBox.style.border = "1px solid #ddd";
    suggestionsBox.style.padding = "10px";
    suggestionsBox.style.display = "none";
    suggestionsBox.style.zIndex = "1000";
    searchInput.parentNode.appendChild(suggestionsBox);

    function updateSuggestionsPosition() {
        const rect = searchInput.getBoundingClientRect();
        suggestionsBox.style.top = `${rect.bottom + window.scrollY}px`;
        suggestionsBox.style.left = `${rect.left + window.scrollX}px`;
        suggestionsBox.style.width = `${rect.width}px`;
    }

    searchInput.addEventListener("input", function () {
        let query = this.value.trim();
        if (query.length > 1) {
            fetch(`../pages/products.php?search=${encodeURIComponent(query)}&ajax=true`)
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = "";
                    updateSuggestionsPosition();

                    if (data.products.length > 0) {
                        let productSection = document.createElement("div");
                        data.products.forEach(p => {
                            let productImage = p.img ? `<img src="${p.img}" class="product-pic" style="width: 30px; height: 30px; border-radius: 8px; object-fit: cover;">`: `<span class="material-symbols-outlined icon">shopping_bag</span>`;

                            let item = document.createElement("div");
                            item.innerHTML = `<a href="../../pages/product.php?id=${p.id}" class="suggestion-item">${productImage} ${p.name}</a>`;
                            productSection.appendChild(item);
                        });
                        suggestionsBox.appendChild(productSection);
                    }

                    if (data.users.length > 0) {
                        let userSection = document.createElement("div");
                        data.users.forEach(u => {
                            let profileImage = u.profile_picture ? `<img src="${u.profile_picture}" class="profile-pic" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">` : `<span class="material-symbols-outlined icon">person</span>`;
                            let item = document.createElement("div");
                            item.innerHTML = `<a href="../../pages/user_profile.php?id=${u.id}" class="suggestion-item">${profileImage} ${u.name}</a>`;
                            userSection.appendChild(item);
                        });
                        suggestionsBox.appendChild(userSection);
                    }

                    if (data.products.length > 0 || data.users.length > 0) {
                        suggestionsBox.style.display = "block";
                    } else {
                        suggestionsBox.style.display = "none";
                    }
                })
                .catch(error => console.error("Error fetching search results:", error));
        } else {
            suggestionsBox.style.display = "none";
        }
    });

    document.addEventListener("click", function (e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = "none";
        }
    });

    window.addEventListener("resize", updateSuggestionsPosition);
});
