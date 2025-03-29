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

// Modal functionality
function setupModal(triggerBtnId, modalId) {
  const triggerBtn = document.getElementById(triggerBtnId);
  const modal = document.getElementById(modalId);

  if (!triggerBtn || !modal) return;

  const closeBtn = modal.querySelector(".admin-modal-close");
  const cancelBtns = modal.querySelectorAll(".admin-modal-cancel");

  triggerBtn.addEventListener("click", function () {
    modal.style.display = "block";
  });

  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      modal.style.display = "none";
    });
  }

  cancelBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      modal.style.display = "none";
    });
  });

  window.addEventListener("click", function (event) {
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
}

// Setup modals
setupModal("confirmEventBtn", "confirmEventModal");
setupModal("completeEventBtn", "completeEventModal");
setupModal("cancelEventBtn", "cancelEventModal");
