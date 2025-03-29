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

// Email Modal
const sendEmailBtn = document.getElementById("sendEmailBtn");
const emailModal = document.getElementById("emailModal");
const closeBtn = emailModal.querySelector(".admin-modal-close");
const cancelBtn = emailModal.querySelector(".admin-modal-cancel");

sendEmailBtn.addEventListener("click", function () {
  emailModal.style.display = "block";
});

closeBtn.addEventListener("click", function () {
  emailModal.style.display = "none";
});

cancelBtn.addEventListener("click", function () {
  emailModal.style.display = "none";
});

window.addEventListener("click", function (event) {
  if (event.target === emailModal) {
    emailModal.style.display = "none";
  }
});
