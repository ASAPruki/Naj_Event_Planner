document.addEventListener("DOMContentLoaded", () => {
  // Mobile Menu Toggle
  const menuToggle = document.querySelector(".menu-toggle");
  const navMenu = document.querySelector(".nav-menu");

  if (menuToggle) {
    menuToggle.addEventListener("click", function () {
      navMenu.classList.toggle("active");
      this.classList.toggle("active");
    });
  }

  // Dropdown Menu for Mobile
  const dropdowns = document.querySelectorAll(".dropdown");

  dropdowns.forEach((dropdown) => {
    const link = dropdown.querySelector("a");

    if (window.innerWidth <= 768) {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        dropdown.classList.toggle("active");
      });
    }
  });

  // Modal Functionality
  const modal = document.getElementById("login-modal");
  const loginButton = document.getElementById("login-button");
  const closeButton = document.querySelector(".close");

  if (loginButton) {
    loginButton.addEventListener("click", (e) => {
      e.preventDefault();
      modal.classList.add("active");
      document.body.style.overflow = "hidden";
    });
  }

  if (closeButton) {
    closeButton.addEventListener("click", () => {
      modal.classList.remove("active");
      document.body.style.overflow = "";
    });
  }

  // Close modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.classList.remove("active");
      document.body.style.overflow = "";
    }
  });

  // Tab Switching in Modal
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  tabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const tabId = this.getAttribute("data-tab");

      // Remove active class from all buttons and contents
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      tabContents.forEach((content) => content.classList.remove("active"));

      // Add active class to current button and content
      this.classList.add("active");
      document.getElementById(`${tabId}-tab`).classList.add("active");
    });
  });

  // Form Validation
  const loginForm = document.getElementById("login-form");
  const signupForm = document.getElementById("signup-form");

  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const email = document.getElementById("login-email").value;
      const password = document.getElementById("login-password").value;

      if (validateEmail(email) && password.length >= 6) {
        // Form is valid, submit to server
        this.submit();
      } else {
        alert("Please enter a valid email and password (min 6 characters)");
      }
    });
  }

  if (signupForm) {
    signupForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const name = document.getElementById("signup-name").value;
      const email = document.getElementById("signup-email").value;
      const password = document.getElementById("signup-password").value;
      const confirmPassword = document.getElementById("signup-confirm").value;

      if (name.length < 3) {
        alert("Name must be at least 3 characters");
        return;
      }

      if (!validateEmail(email)) {
        alert("Please enter a valid email address");
        return;
      }

      if (password.length < 6) {
        alert("Password must be at least 6 characters");
        return;
      }

      if (password !== confirmPassword) {
        alert("Passwords do not match");
        return;
      }

      // Form is valid, submit to server
      this.submit();
    });
  }

  // Testimonial Slider
  const testimonialSlides = document.querySelectorAll(".testimonial-slide");
  const dots = document.querySelectorAll(".dot");
  let currentSlide = 0;

  // Show first slide
  if (testimonialSlides.length > 0) {
    testimonialSlides[0].classList.add("active");
  }

  // Handle dot clicks
  dots.forEach((dot, index) => {
    dot.addEventListener("click", () => {
      showSlide(index);
    });
  });

  // Auto slide change
  if (testimonialSlides.length > 1) {
    setInterval(() => {
      currentSlide = (currentSlide + 1) % testimonialSlides.length;
      showSlide(currentSlide);
    }, 5000);
  }

  function showSlide(index) {
    testimonialSlides.forEach((slide) => slide.classList.remove("active"));
    dots.forEach((dot) => dot.classList.remove("active"));

    testimonialSlides[index].classList.add("active");
    dots[index].classList.add("active");
    currentSlide = index;
  }

  // Helper Functions
  function validateEmail(email) {
    const re =
      /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }
});
