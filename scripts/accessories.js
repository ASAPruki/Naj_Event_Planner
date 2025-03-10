document.addEventListener("DOMContentLoaded", () => {
  // Category Filtering
  const categoryTabs = document.querySelectorAll(".category-tab");
  const accessoryCards = document.querySelectorAll(".accessory-card");

  categoryTabs.forEach((tab) => {
    tab.addEventListener("click", function () {
      // Remove active class from all tabs
      categoryTabs.forEach((t) => t.classList.remove("active"));

      // Add active class to clicked tab
      this.classList.add("active");

      const category = this.getAttribute("data-category");

      // Show/hide accessories based on category
      accessoryCards.forEach((card) => {
        if (category === "all") {
          card.style.display = "block";
        } else {
          const cardCategories = card.getAttribute("data-category").split(" ");
          if (cardCategories.includes(category)) {
            card.style.display = "block";
          } else {
            card.style.display = "none";
          }
        }
      });
    });
  });

  // Image Lazy Loading
  const images = document.querySelectorAll(".accessory-image img");

  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const image = entry.target;
          image.src = image.src; // Load the image
          imageObserver.unobserve(image);
        }
      });
    });

    images.forEach((image) => {
      imageObserver.observe(image);
    });
  } else {
    // Fallback for browsers that don't support IntersectionObserver
    images.forEach((image) => {
      image.src = image.src;
    });
  }

  // Smooth Scrolling for Anchor Links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      if (href !== "#") {
        e.preventDefault();

        const targetElement = document.querySelector(href);

        if (targetElement) {
          window.scrollTo({
            top: targetElement.offsetTop - 100,
            behavior: "smooth",
          });
        }
      }
    });
  });
});
