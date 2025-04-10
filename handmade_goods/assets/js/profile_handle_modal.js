document.addEventListener("DOMContentLoaded", function () {
    // Debug to ensure the script is loaded
    console.log("Profile handle modal script loaded");
    
    function openModal(modalId) {
        let modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = "flex";
            console.log("Opening modal:", modalId);
        } else {
            console.error("Modal not found:", modalId);
        }
    }

    function closeModal(modalId) {
        let modal = document.getElementById(modalId);
        if (modal) modal.style.display = "none";
    }

    function showDeleteUserModal(userId) {
        console.log("Show delete user modal for ID:", userId);
        const deleteUserId = document.getElementById("deleteUserId");
        if (deleteUserId) {
            deleteUserId.value = userId;
            openModal("deleteUserModal");
        } else {
            console.error("deleteUserId input not found");
        }
    }

    function showDeleteListingModal(itemId) {
        console.log("Show delete listing modal for ID:", itemId);
        let deleteModal = document.getElementById("deleteListingModal");
        let inputField = document.getElementById("deleteListingItemId");
        
        if (!deleteModal || !inputField) {
            console.error("Delete Listing Modal or input field not found!");
            return;
        }

        inputField.value = itemId;
        openModal("deleteListingModal");
    }

    function showManageModal(userId, userName, isFrozen) {
        console.log("Show manage modal for user:", userName, "ID:", userId);
        
        // Set user ID for freeze account form
        const freezeUserIdInput = document.getElementById("freezeUserId");
        if (freezeUserIdInput) {
            freezeUserIdInput.value = userId;
        } else {
            console.error("freezeUserId input not found");
        }
        
        // Set user ID for delete account form
        const deleteUserIdInput = document.getElementById("deleteUserIdFromManage");
        if (deleteUserIdInput) {
            deleteUserIdInput.value = userId;
        } else {
            console.error("deleteUserIdFromManage input not found");
        }
        
        // Set the account name in the modal
        const accountNameSpan = document.getElementById("accountName");
        if (accountNameSpan) {
            accountNameSpan.innerText = userName;
        } else {
            console.error("accountName span not found");
        }

        const isFrozen = (isFrozenString === 'true');
        if (isFrozen) {
            document.getElementById("freezeForm").style.display = "none";
            document.getElementById("unfreezeForm").style.display = "inline-block";
        } else {
            document.getElementById("freezeForm").style.display = "inline-block";
            document.getElementById("unfreezeForm").style.display = "none";
        }
        
        openModal("manageModal");
    }

    // Set up global functions first - this ensures they're available for inline onclick handlers
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.showDeleteUserModal = showDeleteUserModal;
    window.showDeleteListingModal = showDeleteListingModal;
    window.showManageModal = showManageModal;
    
    // Add direct click events for all manage buttons
    document.querySelectorAll(".manage-btn").forEach(button => {
        button.addEventListener("click", function(e) {
            console.log("Manage button clicked");
            const userId = this.getAttribute("data-user-id");
            const userName = this.getAttribute("data-user-name");
            const isFrozen = this.getAttribute("data-user-frozen");
            if (userId && userName) {
                console.log("Calling showManageModal with", userId, userName, isFrozen);
                showManageModal(userId, userName, isFrozen);
            } else {
                // Fallback to onclick handler if data attributes not found
                console.log("No data attributes found, trying inline onclick handler");
            }
        });
    });

    document.querySelectorAll(".cancel-btn").forEach(button => {
        button.addEventListener("click", function () {
            let parentModal = this.closest(".modal");
            if (parentModal) {
                parentModal.style.display = "none";
            }
        });
    });

    window.onclick = function (event) {
        document.querySelectorAll(".modal").forEach(modal => {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    };
    
    console.log("Modal handlers attached and functions exposed to window");
});
