document.addEventListener("DOMContentLoaded", () => {
  // Modal Functionality
  const contactButton = document.getElementById("contact-organizer");
  const cancelButton = document.getElementById("cancel-event");
  const reviewButton = document.getElementById("leave-review");
  const cancelModal = document.getElementById("cancel-modal");
  const reviewModal = document.getElementById("review-modal");
  const closeButtons = document.querySelectorAll(".close, .close-modal");

  // Open Cancel Modal
  if (cancelButton) {
    cancelButton.addEventListener("click", () => {
      cancelModal.classList.add("active");
      document.body.style.overflow = "hidden";
    });
  }

  // Open Review Modal
  if (reviewButton) {
    reviewButton.addEventListener("click", () => {
      reviewModal.classList.add("active");
      document.body.style.overflow = "hidden";
    });
  }

  // Close Modals
  closeButtons.forEach((button) => {
    button.addEventListener("click", () => {
      if (cancelModal) cancelModal.classList.remove("active");
      if (reviewModal) reviewModal.classList.remove("active");
      document.body.style.overflow = "";
    });
  });

  // Close modal when clicking outside
  window.addEventListener("click", (e) => {
    if (
      (cancelModal && e.target === cancelModal) ||
      (reviewModal && e.target === reviewModal)
    ) {
      if (cancelModal) cancelModal.classList.remove("active");
      if (reviewModal) reviewModal.classList.remove("active");
      document.body.style.overflow = "";
    }
  });

  // Form Validation - Contact Form
  const contactForm = document.getElementById("contact-form");

  if (contactForm) {
    contactForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const subject = document.getElementById("subject").value;
      const message = document.getElementById("message").value;

      if (subject.trim() === "") {
        alert("Please enter a subject");
        return;
      }

      if (message.trim() === "") {
        alert("Please enter a message");
        return;
      }

      // If validation passes, submit the form
      this.submit();
    });
  }

  // Form Validation - Cancel Form
  const cancelForm = document.getElementById("cancel-form");

  if (cancelForm) {
    cancelForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const reason = document.getElementById("cancel-reason").value;

      if (reason.trim() === "") {
        alert("Please provide a reason for cancellation");
        return;
      }

      // If validation passes, submit the form
      this.submit();
    });
  }

  // Form Validation - Review Form
  const reviewForm = document.getElementById("review-form");

  if (reviewForm) {
    reviewForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const rating = document.querySelector('input[name="rating"]:checked');
      const reviewText = document.getElementById("review-text").value;

      if (!rating) {
        alert("Please select a rating");
        return;
      }

      if (reviewText.trim() === "") {
        alert("Please write a review");
        return;
      }

      // If validation passes, submit the form
      this.submit();
    });
  }

  // Book Again Functionality
  const bookAgainButton = document.querySelector(".book-again");

  if (bookAgainButton) {
    bookAgainButton.addEventListener("click", function () {
      const eventType = this.getAttribute("data-event-type");
      window.location.href = `reservation.php?event_type=${eventType}`;
    });
  }
});
