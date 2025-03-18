document.addEventListener("DOMContentLoaded", function () {
    function openModal(modalId) {
        let modal = document.getElementById(modalId);
        if (modal) modal.style.display = "flex";
    }

    function closeModal(modalId) {
        let modal = document.getElementById(modalId);
        if (modal) modal.style.display = "none";
    }

    function showDeleteUserModal(userId) {
        document.getElementById("deleteUserId").value = userId;
        openModal("deleteUserModal");
    }

    function showDeleteListingModal(itemId) {
        let deleteModal = document.getElementById("deleteListingModal");
        let inputField = deleteModal.querySelector("input[name='item_id']");
        
        if (!deleteModal || !inputField) {
            console.error("Delete Listing Modal or input field not found!");
            return;
        }

        inputField.value = itemId;
        openModal("deleteListingModal");
    }

    function showManageModal(userId, userName) {
        document.getElementById("manageUserId").value = userId;
        document.getElementById("deleteUserId").value = userId;
        document.getElementById("accountName").innerText = userName;
        openModal("manageModal");
    }

    let deleteFromManageBtn = document.getElementById("deleteFromManage");
    if (deleteFromManageBtn) {
        deleteFromManageBtn.addEventListener("click", function () {
            closeModal("manageModal");
            openModal("deleteUserModal");
        });
    }

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

    window.showDeleteUserModal = showDeleteUserModal;
    window.showDeleteListingModal = showDeleteListingModal;
    window.showManageModal = showManageModal;
});
