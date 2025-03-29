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

// Status Update Modal
const statusModal = document.getElementById("statusModal");
const statusForm = document.getElementById("statusForm");
const eventIdInput = document.getElementById("event_id");
const eventStatusSelect = document.getElementById("event_status");
const closeBtn = statusModal.querySelector(".admin-modal-close");
const cancelBtn = statusModal.querySelector(".admin-modal-cancel");
const updateStatusBtns = document.querySelectorAll(".update-status-btn");

updateStatusBtns.forEach((btn) => {
  btn.addEventListener("click", function () {
    const eventId = this.getAttribute("data-id");
    const currentStatus = this.getAttribute("data-current");

    eventIdInput.value = eventId;
    eventStatusSelect.value = currentStatus;

    statusModal.style.display = "block";
  });
});

closeBtn.addEventListener("click", function () {
  statusModal.style.display = "none";
});

cancelBtn.addEventListener("click", function () {
  statusModal.style.display = "none";
});

window.addEventListener("click", function (event) {
  if (event.target === statusModal) {
    statusModal.style.display = "none";
  }
});
