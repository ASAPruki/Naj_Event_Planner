document.addEventListener("DOMContentLoaded", () => {
  // Toggle sidebar on mobile
  const toggleSidebar = document.getElementById("toggleSidebar");
  const adminSidebar = document.getElementById("adminSidebar");
  const adminMain = document.getElementById("adminMain");

  if (toggleSidebar) {
    toggleSidebar.addEventListener("click", function () {
      adminSidebar.classList.toggle("active");
      adminMain.classList.toggle("sidebar-active");
    });
  }

  // Toggle password section
  const changePasswordCheckbox = document.getElementById("change_password");
  const passwordSection = document.getElementById("password_section");

  if (changePasswordCheckbox && passwordSection) {
    changePasswordCheckbox.addEventListener("change", function () {
      passwordSection.style.display = this.checked ? "block" : "none";

      // Reset password field when hiding
      if (!this.checked) {
        document.getElementById("new_password").value = "";
      }
    });
  }

  const editAdminForm = document.getElementById("edit-admin-form");

  // Validation for the add new user form
  if (editAdminForm) {
    editAdminForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validatePhone(phone)) {
        alert(
          "Please enter a valid phone number.\nAccepted formats:\n12345678\n12 345678\n12 345 678"
        );
        return;
      }

      // If validation passes, submit the form
      editAdminForm.submit();
    });
  }

  function validatePhone(phone) {
    let phoneInput = document.getElementById("phone").value.trim();

    let phonePattern = /^\d{2}\s?\d{3}\s?\d{3}$/;

    if (!phonePattern.test(phoneInput)) {
      return false; // Prevent form submission
    }
    return true; // Allow form submission
  }
});
