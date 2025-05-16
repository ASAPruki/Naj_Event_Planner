document.addEventListener("DOMContentLoaded", function () {
  const toggleSidebar = document.getElementById("toggleSidebar");
  const adminSidebar = document.getElementById("adminSidebar");
  const adminMain = document.getElementById("adminMain");

  if (toggleSidebar) {
    toggleSidebar.addEventListener("click", function () {
      adminSidebar.classList.toggle("active");
      adminMain.classList.toggle("sidebar-active");
    });
  }

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

  const alertCloseButtons = document.querySelectorAll(".admin-alert-close");
  alertCloseButtons.forEach((button) => {
    button.addEventListener("click", function () {
      this.parentElement.style.display = "none";
    });
  });

  // Approve payment modal
  const approveButtons = document.querySelectorAll(".approve-payment");
  const approveModal = document.getElementById("approvePaymentModal");
  const modalClose = approveModal.querySelector(".admin-modal-close");
  const modalCancel = approveModal.querySelector(".admin-modal-cancel");
  const recordIdInput = document.getElementById("record_id");
  const paymentTypeInput = document.getElementById("payment_type");
  const reservationIdInput = document.getElementById("reservation_id");

  approveButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const recordId = this.getAttribute("data-id");
      const reservationId = this.getAttribute("data-reservation");
      const paymentType = this.getAttribute("data-type");

      recordIdInput.value = recordId;
      paymentTypeInput.value = paymentType;
      reservationIdInput.value = reservationId;

      approveModal.style.display = "block";
    });
  });

  if (modalClose) {
    modalClose.addEventListener("click", function () {
      approveModal.style.display = "none";
    });
  }

  if (modalCancel) {
    modalCancel.addEventListener("click", function () {
      approveModal.style.display = "none";
    });
  }

  window.addEventListener("click", function (event) {
    if (event.target === approveModal) {
      approveModal.style.display = "none";
    }
  });
});
