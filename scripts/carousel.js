document.addEventListener("DOMContentLoaded", function () {
  const slides = document.querySelectorAll(".carousel-slide");
  const prevBtn = document.querySelector(".carousel-prev");
  const nextBtn = document.querySelector(".carousel-next");
  let currentSlide = 0;
  let slideInterval;

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.toggle("active", i === index);
    });
  }

  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
  }

  function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(currentSlide);
  }

  if (prevBtn && nextBtn) {
    prevBtn.addEventListener("click", () => {
      prevSlide();
      resetAutoPlay();
    });

    nextBtn.addEventListener("click", () => {
      nextSlide();
      resetAutoPlay();
    });
  }

  function startAutoPlay() {
    slideInterval = setInterval(nextSlide, 3000);
  }

  function resetAutoPlay() {
    clearInterval(slideInterval);
    startAutoPlay();
  }

  showSlide(currentSlide);
  startAutoPlay();
});
