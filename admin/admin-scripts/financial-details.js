document.addEventListener("DOMContentLoaded", function () {
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

  // Alert close button
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
  const paymentTypeInput = document.getElementById("payment_type");

  approveButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const paymentType = this.getAttribute("data-type");
      paymentTypeInput.value = paymentType;
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

  // Decline payment modal
  const declineButtons = document.querySelectorAll(".decline-payment");
  const declineModal = document.getElementById("declinePaymentModal");
  const declineModalClose = declineModal.querySelector(".admin-modal-close");
  const declineModalCancel = declineModal.querySelector(".admin-modal-cancel");
  const declineRecordIdInput = document.getElementById("decline_record_id");
  const declinePaymentTypeInput = document.getElementById(
    "decline_payment_type"
  );
  const declineReservationIdInput = document.getElementById(
    "decline_reservation_id"
  );

  declineButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const recordId = this.getAttribute("data-id");
      const paymentType = this.getAttribute("data-type");
      const reservationId = this.getAttribute("data-reservation");

      declineRecordIdInput.value = recordId;
      declinePaymentTypeInput.value = paymentType;
      declineReservationIdInput.value = reservationId;

      declineModal.style.display = "block";
    });
  });

  if (declineModalClose) {
    declineModalClose.addEventListener("click", function () {
      declineModal.style.display = "none";
    });
  }

  if (declineModalCancel) {
    declineModalCancel.addEventListener("click", function () {
      declineModal.style.display = "none";
    });
  }

  window.addEventListener("click", function (event) {
    if (event.target === declineModal) {
      declineModal.style.display = "none";
    }
  });

  document.querySelectorAll(".clickable-image").forEach(function (div) {
    div.addEventListener("click", function () {
      const imgSrc = this.getAttribute("data-img-src");
      document.getElementById("modal-img").src = imgSrc;
      document.getElementById("image-modal").style.display = "block";
    });
  });

  document.querySelector(".close-modal").addEventListener("click", function () {
    document.getElementById("image-modal").style.display = "none";
  });

  window.addEventListener("click", function (e) {
    const modal = document.getElementById("image-modal");
    if (e.target == modal) {
      modal.style.display = "none";
    }
  });

  // Price editing functionality
  const editPriceBtn = document.querySelector(".edit-price-btn");
  const cancelEditBtn = document.querySelector(".cancel-edit-btn");
  const priceDisplay = document.getElementById("price-display");
  const priceEditForm = document.getElementById("price-edit-form");

  editPriceBtn.addEventListener("click", function () {
    priceDisplay.style.display = "none";
    priceEditForm.style.display = "block";
  });

  cancelEditBtn.addEventListener("click", function () {
    priceDisplay.style.display = "grid";
    priceEditForm.style.display = "none";
  });

  // Calculate deposit percentage
  const fullPriceInput = document.getElementById("full_price");
  const depositAmountInput = document.getElementById("deposit_amount");
  const depositPercentage = document.getElementById("deposit-percentage");

  function updateDepositPercentage() {
    const fullPrice = parseFloat(fullPriceInput.value);
    const depositAmount = parseFloat(depositAmountInput.value);

    if (fullPrice > 0 && depositAmount > 0) {
      const percentage = (depositAmount / fullPrice) * 100;
      depositPercentage.textContent = percentage.toFixed(2) + "% of full price";
    } else {
      depositPercentage.textContent = "0% of full price";
    }
  }

  fullPriceInput.addEventListener("input", updateDepositPercentage);
  depositAmountInput.addEventListener("input", updateDepositPercentage);
});
