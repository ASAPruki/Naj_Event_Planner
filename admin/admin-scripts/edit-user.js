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

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", function (event) {
    const isClickInsideSidebar = adminSidebar.contains(event.target);
    const isClickInsideToggle = toggleSidebar.contains(event.target);

    if (
      window.innerWidth <= 992 &&
      !isClickInsideSidebar &&
      !isClickInsideToggle &&
      adminSidebar.classList.contains("active")
    ) {
      adminSidebar.classList.remove("active");
      adminMain.classList.remove("sidebar-active");
    }
  });

  const editUserForm = document.getElementById("edit-user-form");

  // Validation for the add new user form
  if (editUserForm) {
    editUserForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validatePhone(phone)) {
        alert(
          "Please enter a valid phone number.\nAccepted formats:\n12345678\n12 345678\n12 345 678"
        );
        return;
      }

      // If validation passes, submit the form
      editUserForm.submit();
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
