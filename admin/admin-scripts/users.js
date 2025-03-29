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

  // Toggle add user form
  const toggleAddForm = document.getElementById("toggleAddForm");
  const addUserForm = document.getElementById("addUserForm");

  if (toggleAddForm && addUserForm) {
    toggleAddForm.addEventListener("click", function () {
      if (addUserForm.style.display === "none") {
        addUserForm.style.display = "block";
        toggleAddForm.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
      } else {
        addUserForm.style.display = "none";
        toggleAddForm.innerHTML = '<i class="fas fa-plus"></i> Show Form';
      }
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

  const addNewUserForm = document.getElementById("add-new-user-form");

  // Validation for the add new user form
  if (addNewUserForm) {
    addUserForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validatePhone(phone)) {
        alert(
          "Please enter a valid phone number.\nAccepted formats:\n12345678\n12 345678\n12 345 678"
        );
        return;
      }

      // If validation passes, submit the form
      addNewUserForm.submit();
    });
  }

  function validatePhone(phone) {
    let phoneInput = document.getElementById("phone").value.trim();

    let phonePattern = /^\d{2}\s?\d{3}\s?\d{3}$/;

    if (!phonePattern.test(phoneInput)) {
      alert(
        "Please enter a valid phone number.\nAccepted formats:\n12345678\n12 345678\n12 345 678"
      );
      return false; // Prevent form submission
    }
    return true; // Allow form submission
  }
});
