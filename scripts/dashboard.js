document.addEventListener("DOMContentLoaded", () => {
  // Mobile sidebar toggle
  const sidebarToggle = document.getElementById("sidebar-toggle");
  const dashboardSidebar = document.querySelector(".dashboard-sidebar");

  if (sidebarToggle && dashboardSidebar) {
    console.log("Sidebar toggle and sidebar elements found");

    sidebarToggle.addEventListener("click", function (e) {
      e.stopPropagation(); // Prevent event from bubbling up
      console.log("Sidebar toggle clicked");
      dashboardSidebar.classList.toggle("active");
      this.classList.toggle("active");
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", (e) => {
      if (
        window.innerWidth <= 992 &&
        dashboardSidebar.classList.contains("active") &&
        !dashboardSidebar.contains(e.target) &&
        e.target !== sidebarToggle
      ) {
        dashboardSidebar.classList.remove("active");
        sidebarToggle.classList.remove("active");
      }
    });
  } else {
    console.error("Sidebar toggle or sidebar elements not found");
  }

  // Tab Navigation
  const tabLinks = document.querySelectorAll(
    ".dashboard-nav a:not(#logout), .tab-link"
  );
  const tabContents = document.querySelectorAll(".dashboard-tab");

  // Special handling for logout link
  const logoutLink = document.querySelector(".dashboard-nav #logout");
  if (logoutLink) {
    logoutLink.addEventListener("click", (e) => {
      e.preventDefault();
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "../APIs/logout.php";
      }
    });
  }

  // Tab switching functionality
  tabLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      const tabId = this.getAttribute("data-tab");
      if (!tabId) return;

      // Remove active class from all links and tabs
      tabLinks.forEach((link) => link.classList.remove("active"));
      tabContents.forEach((tab) => tab.classList.remove("active"));

      // Add active class to current link and tab
      this.classList.add("active");

      const tabElement = document.getElementById(tabId);
      if (tabElement) {
        tabElement.classList.add("active");
        // Scroll to top of tab content
        const dashboardContent = document.querySelector(".dashboard-content");
        if (dashboardContent) {
          dashboardContent.scrollTop = 0;
        }
      }
    });
  });

  // Form Validation - Profile Update
  const profileForm = document.getElementById("profile-update-form");

  if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const name = document.getElementById("name").value;
      const email = document.getElementById("email").value;
      const phone = document.getElementById("phone").value;

      if (name.trim() === "") {
        alert("Please enter your name");
        return;
      }

      if (!validateEmail(email)) {
        alert("Please enter a valid email address");
        return;
      }

      if (!validatePhone(phone)) {
        alert(
          "Please enter a valid phone number.\nAccepted formats:\n12345678\n12 345678\n12 345 678"
        );
        return;
      }

      // If validation passes, submit the form
      this.submit();
    });
  }

  // Form Validation - Password Update
  const passwordForm = document.getElementById("password-update-form");

  if (passwordForm) {
    passwordForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const currentPassword = document.getElementById("current-password").value;
      const newPassword = document.getElementById("new-password").value;
      const confirmPassword = document.getElementById("confirm-password").value;

      if (currentPassword.trim() === "") {
        alert("Please enter your current password");
        return;
      }

      if (newPassword.length < 6) {
        alert("New password must be at least 6 characters");
        return;
      }

      if (newPassword !== confirmPassword) {
        alert("New passwords do not match");
        return;
      }

      // If validation passes, submit the form
      this.submit();
    });
  }

  // Book Again Functionality
  const bookAgainButtons = document.querySelectorAll(".book-again");

  bookAgainButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const eventType = this.getAttribute("data-event-type");
      window.location.href = `reservation.php?event_type=${eventType}`;
    });
  });

  // Helper Functions
  function validateEmail(email) {
    const re =
      /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }

  function validatePhone(phone) {
    const re = /^\d{2}\s?\d{3}\s?\d{3}$/;
    return re.test(phone);
  }
});
