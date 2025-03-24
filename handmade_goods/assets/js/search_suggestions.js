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

    categoryIcons = {
        "Kitchenware": "skillet",
        "Accessories": "diamond",
        "Apparel": "apparel",
        "Home Decor": "cottage",
        "Personal Care": "health_and_beauty",
        "Stationery": "stylus_note",
        "Toys": "toys",
        "Art": "palette",
        "Seasonal": "park",
        "Gift Sets": "featured_seasonal_and_gifts",
        "Wallets and Purses": "wallet",
        "Storage": "inventory_2"
    };

    searchInput.addEventListener("input", function () {
        let query = this.value.trim();
        let noValidSuggestions = false;
        let loggedInUserId = typeof window.loggedInUserId !== "undefined" ? parseInt(window.loggedInUserId, 10) : null;
        if (query.length > 1) {
            fetch(`../pages/products.php?search=${encodeURIComponent(query)}&ajax=true`)
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = "";
                    updateSuggestionsPosition();

                    if (data.products.length > 0) {
                        let productSection = document.createElement("div");
                        productSection.innerHTML = `<p class="suggestion-label">Products</p>`;
                        data.products.forEach(p => {
                            if (loggedInUserId && p.user_id == loggedInUserId) return;
                            let productImage = p.img ? `<img src="${p.img}" class="product-pic" style="width: 30px; height: 30px; border-radius: 8px; object-fit: cover;">`: `<span class="material-symbols-outlined icon">shopping_bag</span>`;

                            let item = document.createElement("div");
                            item.innerHTML = `<a href="../pages/product.php?id=${p.id}" class="suggestion-item">${productImage} ${p.name}</a>`;
                            productSection.appendChild(item);
                        });
                        if (productSection.querySelectorAll('.suggestion-item').length > 0) {
                            suggestionsBox.appendChild(productSection);
                        } else{
                            noValidSuggestions = true;
                        }
                    }

                    if (data.users.length > 0) {
                        let userSection = document.createElement("div");
                        userSection.innerHTML = `<p class="suggestion-label">Sellers</p>`;
                        data.users.forEach(u => {
                            if (loggedInUserId && u.id == loggedInUserId) return;
                            let profileImage = u.profile_picture ? `<img src="${u.profile_picture}" class="profile-pic" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">` : `<span class="material-symbols-outlined icon">person</span>`;
                            let item = document.createElement("div");
                            item.innerHTML = `<a href="../pages/user_profile.php?id=${u.id}" class="suggestion-item">${profileImage} ${u.name}</a>`;
                            userSection.appendChild(item);
                        });
                        if (userSection.querySelectorAll('.suggestion-item').length > 0) {
                            suggestionsBox.appendChild(userSection);
                        } else{
                            noValidSuggestions = true;
                        }
                    }

                    if (data.categories.length > 0) {
                        let categorySection = document.createElement("div");
                        categorySection.innerHTML = `<p class="suggestion-label">Categories</p>`;
                        data.categories.forEach(c => {
                            let categoryImage = `<span class="material-symbols-outlined icon">${categoryIcons[c.category] != "" ? categoryIcons[c.category] : "category"}</span>`;
                            let item = document.createElement("div");
                            item.innerHTML = `<a href="../pages/products.php?category=${c.category}" class="suggestion-item">${categoryImage} ${c.category}</a>`;
                            categorySection.appendChild(item);
                        });
                        if (categorySection.querySelectorAll('.suggestion-item').length > 0) {
                            suggestionsBox.appendChild(categorySection);
                        } else{
                            noValidSuggestions = true;
                        }
                    }

                    if ((data.products.length > 0 || data.users.length > 0 || data.categories.length > 0) && noValidSuggestions == false) {
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
