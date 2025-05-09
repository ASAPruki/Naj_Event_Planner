document.addEventListener("DOMContentLoaded", function () {
  // Toggle sidebar on mobile
  const toggleSidebar = document.getElementById("toggleSidebar");
  const adminSidebar = document.getElementById("adminSidebar");
  const adminMain = document.getElementById("adminMain");

  if (toggleSidebar) {
    toggleSidebar.addEventListener("click", function (e) {
      e.stopPropagation(); // Prevent this click from triggering the outside-close logic
      adminSidebar.classList.toggle("active");
      adminMain.classList.toggle("sidebar-active");
    });
  }

  // Close sidebar when clicking outside (on mobile)
  document.addEventListener("click", function (event) {
    const isClickInsideSidebar = adminSidebar?.contains(event.target);
    const isClickInsideToggle = toggleSidebar?.contains(event.target);

    if (
      window.innerWidth <= 992 &&
      !isClickInsideSidebar &&
      !isClickInsideToggle &&
      adminSidebar?.classList.contains("active")
    ) {
      adminSidebar.classList.remove("active");
      adminMain.classList.remove("sidebar-active");
    }
  });

  // Toggle add admin form
  const toggleAddForm = document.getElementById("toggleAddForm");
  const addAdminForm = document.getElementById("addAdminForm");

  if (toggleAddForm && addAdminForm) {
    toggleAddForm.addEventListener("click", function () {
      if (addAdminForm.style.display === "none") {
        addAdminForm.style.display = "block";
        toggleAddForm.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
      } else {
        addAdminForm.style.display = "none";
        toggleAddForm.innerHTML = '<i class="fas fa-plus"></i> Show Form';
      }
    });
  }

  // Delete confirmation modal
  const deleteButtons = document.querySelectorAll(".delete-admin");
  const deleteModal = document.getElementById("deleteModal");
  const deleteAdminName = document.getElementById("deleteAdminName");
  const deleteAdminId = document.getElementById("deleteAdminId");
  const cancelDelete = document.getElementById("cancelDelete");

  deleteButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.getAttribute("data-id");
      const name = this.getAttribute("data-name");

      deleteAdminId.value = id;
      deleteAdminName.textContent = name;
      deleteModal.style.display = "block";
    });
  });

  if (cancelDelete) {
    cancelDelete.addEventListener("click", function () {
      deleteModal.style.display = "none";
    });
  }

  // Close modal when clicking outside
  window.addEventListener("click", function (event) {
    if (event.target === deleteModal) {
      deleteModal.style.display = "none";
    }
  });

  function validatePhone() {
    let phoneInput = document.getElementById("phone").value.trim();
    let phonePattern = /^\d{2}\s?\d{3}\s?\d{3}$/;

    if (!phonePattern.test(phoneInput)) {
      alert(
        "Please enter a valid phone number.\nAccepted formats:\n12345678\n12 345678\n12 345 678"
      );
      return false;
    }
    return true;
  }
});
